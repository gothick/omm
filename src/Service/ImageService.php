<?php

namespace App\Service;

use App\Entity\Image;
use App\Repository\WanderRepository;
use App\Utils\ExifHelper;
use Deployer\Logger\Logger;
use Exception;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPExif\Adapter\Exiftool;
use PHPExif\Reader\Reader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
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

    /** @var string */
    private $exiftoolPath;

    public function __construct(
        UploaderHelper $uploaderHelper,
        CacheManager $imagineCacheManager,
        UrlGeneratorInterface $router,
        LoggerInterface $logger,
        WanderRepository $wanderRepository,
        string $imagesDirectory,
        ?string $exiftoolPath)
    {
        $this->uploaderHelper = $uploaderHelper;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->router = $router;
        $this->logger = $logger;
        $this->wanderRepository = $wanderRepository;
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

    public function setPropertiesFromEXIF(
            Image $image,
            bool $updateRelatedWander = true
        ): void
    {
        if ($image->getMimeType() !== 'image/jpeg') {
            $this->logger->info('Ignoring non-JPEG file when trying to set properties from EXIT.');
            return;
        }

        try {
            $exif = $this->reader->read($this->imagesDirectory . '/' . $image->getName());
            /** @var ExifHelperInterface */
            $exifHelper = new ExifHelper($exif);

            $image->setTitle($exifHelper->getTitle());
            $image->setDescription($exifHelper->getDescription());
            $image->setLatlng($exifHelper->getGPS());
            $image->setKeywords($exifHelper->getKeywords());
            $image->setRating($exifHelper->getRating());
            $image->setLocation($exifHelper->getLocation());

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

