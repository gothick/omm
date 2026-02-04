<?php

namespace App\Service;

use App\Entity\Image;
use App\Repository\WanderRepository;
use App\Utils\ExifHelper;
use Exception;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPExif\Adapter\Exiftool;
use PHPExif\Reader\Reader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use App\Service\NeighbourhoodService;
use App\Utils\ExifHelperInterface;

class ImageService {

    /** @var UploaderHelper */
    private $uploaderHelper;

    /** @var CacheManager */
    private $imagineCacheManager;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var LoggerInterface */
    private $logger;

    /** @var WanderRepository */
    private $wanderRepository;

    /** @var Reader */
    private $reader;

    /** @var string */
    private $imagesDirectory;

    /** @var NeighbourhoodService */
    private $neighbourhoodService;

    /** @var string */
    private $exiftoolPath;

    public function __construct(
        UploaderHelper $uploaderHelper,
        CacheManager $imagineCacheManager,
        UrlGeneratorInterface $router,
        LoggerInterface $logger,
        WanderRepository $wanderRepository,
        NeighbourhoodService $neighbourhoodService,
        string $imagesDirectory,
        ?string $exiftoolPath)
    {
        $this->uploaderHelper = $uploaderHelper;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->router = $router;
        $this->logger = $logger;
        $this->wanderRepository = $wanderRepository;
        $this->neighbourhoodService = $neighbourhoodService;
        $this->imagesDirectory = $imagesDirectory;
        $this->exiftoolPath = $exiftoolPath;

        if ($exiftoolPath !== null) {
            // Will throw if path is wrong
            $adapter = new Exiftool([
                'toolpath' => $exiftoolPath
            ]);
            $this->reader = new Reader($adapter);
        } else {
            $this->reader = Reader::factory(Reader::TYPE_EXIFTOOL);
        }
    }

    public function setCalculatedImageUris(Image $image): void
    {
        $image_asset_path = $this->uploaderHelper->asset($image);
        $image->setImageUri($image_asset_path);
        $image->setMarkerImageUri($this->imagineCacheManager->getBrowserPath($image_asset_path, 'marker_thumb'));
        $image->setMediumImageUri($this->imagineCacheManager->getBrowserPath($image_asset_path, 'map_popup_image'));
        $image->setImageShowUri($this->router->generate('image_show', ['id' => $image->getId()]));
    }

    /**
     * Neighbourhood was a late addition and we have a Command to update existing ones. We don't want to touch
     * any other existing data, so this is a special one-off to set Neighbourhood.
     */
    public function setNeighbourhoodFromEXIF(Image $image): void
    {
        if ($image->getMimeType() !== 'image/jpeg') {
            $this->logger->info('Ignoring non-JPEG file when trying to set properties from EXIT.');
            return;
        }

        try {
            $exif = $this->reader->read($this->imagesDirectory . '/' . $image->getName());
            /** @var ExifHelperInterface */
            $exifHelper = new ExifHelper($exif);
            // Don't want to overwrite anything we've already set.
            if (!$image->hasNeighbourhood()) {
                $image->setNeighbourhood($exifHelper->getLocation());
            }
        }
        catch(Exception $e) {
            // We've started to rely on the information gathered here, so I think
            // this should be a proper error now.
            throw new Exception('Error getting image Exif information: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function setPropertiesFromEXIF(
            Image $image,
            bool $updateRelatedWander = true
        ): void
    {
        if ($image->getMimeType() !== 'image/jpeg') {
            $this->logger->info('Ignoring non-JPEG file when trying to set properties from EXIF.');
            return;
        }

        try {
            $exif = $this->reader->read($this->imagesDirectory . '/' . $image->getName());
            /** @var ExifHelperInterface */
            $exifHelper = new ExifHelper($exif);

            $image->setTitle($exifHelper->getTitle());
            $image->setDescription($exifHelper->getDescription());
            $image->setLatlng($exifHelper->getGPS());
            $image->setTagsText(implode(",", $exifHelper->getKeywords() ?? []));
            $image->setRating($exifHelper->getRating());

            $neighbourhood = $exifHelper->getLocation();
            if ($neighbourhood === null && $image->hasLatlng()) {
                // If we didn't set the location from the EXIF, this will try setting it
                // from the GPS co-ordinates.
                $neighbourhood = $this->neighbourhoodService->getNeighbourhood($image->getLatitude(), $image->getLongitude());
            }
            if ($neighbourhood !== null) {
                $image->setNeighbourhood($neighbourhood);
            }

            $capturedAt = $exifHelper->getCreationDate();
            if ($capturedAt instanceof \DateTime) {
                $image->setCapturedAt($capturedAt);
                if ($updateRelatedWander) {
                    // Try and find associated wander by looking for
                    // wanders whose timespan includes this image.
                    // TODO: Work out a way of adding some windage so images
                    // shot a little time either side of the track log still
                    // match.
                    $wander = $this->wanderRepository->findFirstWhereIncludesDate($capturedAt);
                    if ($wander !== null) {
                        $image->setWander($wander);
                    }
                }
            }
        }
        catch(Exception $e) {
            // We've started to rely on the information gathered here, so I think
            // this should be a proper error now.
            throw new Exception('Error getting image Exif information: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}

