<?php

namespace App\Service;

use App\Entity\Image;
use App\Repository\WanderRepository;
use Deployer\Logger\Logger;
use Exception;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPExif\Adapter\Exiftool;
use PHPExif\Reader\Reader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

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
            bool $updateRelatedWanders = true
        ): void
    {
        if ($image->getMimeType() == 'image/jpeg') {
            try {
                $exif = $this->reader->read($this->imagesDirectory . '/' . $image->getName());

                $title = $exif->getTitle();
                $description = $exif->getCaption();
                $gps = $exif->getGPS();
                $keywords = $exif->getKeywords();
                $capturedAt = $exif->getCreationDate();

                // Dig slightly deeper
                $rating = false;
                $raw = $exif->getRawData();
                if (array_key_exists('XMP-xmp:Rating', $raw)) {
                    $rating = $raw['XMP-xmp:Rating'];
                }

                if (is_string($title)) {
                    $image->setTitle($title);
                }

                if (is_string($description)) {
                    $image->setDescription($description);
                }

                // The PHPDoc for getGPS says it returns an array, but
                // it definitely seems to return a string.
                if (is_string(/** @scrutinizer ignore-type */ $gps)) {
                    $array = array_map('doubleval', explode(',', $gps));
                    $image->setLatlng($array);
                }

                // Similarly, PHPDoc for getKeywords says it returns an
                // array, but if there's only one keyword it can return
                // a plain string.
                if (is_string(/** @scrutinizer ignore-type */ $keywords)) {
                    $keywords = [ $keywords ];
                }

                if (is_array($keywords)) {
                    $image->setKeywords($keywords);
                }

                if ($capturedAt instanceof \DateTime) {
                    $image->setCapturedAt($capturedAt);

                    // We can also try and find an associated wander by looking for
                    // wanders whose timespan includes this image.
                    if ($updateRelatedWanders) {
                        $wanders = $this->wanderRepository->findWhereIncludesDate($capturedAt);
                        foreach ($wanders as $wander) {
                            $image->addWander($wander);
                        }
                    }
                }
                if (is_int($rating)) {
                    $image->setRating($rating);
                }
            }
            catch(Exception $e) {
                // We've started to rely on the information gathered here, so I think
                // this should be a proper error now.
                throw new Exception('Error getting image Exif information: ' . $e->getMessage(), $e->getCode(), $e);
            }
        } else {
            $this->logger->info('Ignoring non-JPEG file when trying to set properties from EXIT.');
        }
    }
}

