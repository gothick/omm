<?php 

namespace App\Service;

use App\Entity\Image;
use App\Repository\WanderRepository;
use Deployer\Logger\Logger;
use Exception;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPExif\Reader\Reader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    public function __construct(
        UploaderHelper $uploaderHelper, 
        CacheManager $imagineCacheManager,
        UrlGeneratorInterface $router,
        LoggerInterface $logger,
        WanderRepository $wanderRepository)
    {
        $this->uploaderHelper = $uploaderHelper;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->router = $router;
        $this->logger = $logger;
        $this->wanderRepository = $wanderRepository;
    }

    public function setCalculatedImageUris(Image $image): void
    {
        $image_asset_path = $this->uploaderHelper->asset($image);
        $image->setImageUri($image_asset_path);
        $image->setMarkerImageUri($this->imagineCacheManager->getBrowserPath($image_asset_path, 'marker_thumb'));
        $image->setMediumImageUri($this->imagineCacheManager->getBrowserPath($image_asset_path, 'square_thumb_600'));
        $image->setImageEntityAdminUri($this->router->generate('admin_image_show', ['id' => $image->getId()]));
    }
    public function setPropertiesFromEXIF(Image $image): void
    {
        if ($image->getMimeType() == 'image/jpeg') {
            try {
                $reader = Reader::factory(Reader::TYPE_NATIVE);
                $exif = $reader->read($image->getImageFile()->getPathname());

                $title = $exif->getTitle();
                $description = $exif->getCaption();
                $gps = $exif->getGPS();
                $keywords = $exif->getKeywords();
                $capturedAt = $exif->getCreationDate();
                
                if ($title !== false) {
                    $image->setTitle($title);
                }
                
                if ($description !== false) {
                    $image->setDescription($description);
                }

                if ($gps !== false) {
                    $array = array_map('doubleval', explode(',', $gps));
                    $image->setLatlng($array);
                }
                if ($keywords !== false) {
                    $keywords = is_array($keywords) ? $keywords : array($keywords);
                    $image->setKeywords($keywords);
                }
                if ($capturedAt !== false) {
                    $image->setCapturedAt($capturedAt);

                    // We can also try and find an associated wander by looking for 
                    // wanders whose timespan includes this image.
                    $wanders = $this->wanderRepository->findWhereIncludesDate($capturedAt);
                    foreach ($wanders as $wander) {
                        $image->addWander($wander);
                    }
                }
            }
            catch(Exception $e) {
                // It's not that important if we can't update the data
                // from the JPEG image. We can always set it manually later.
                $this->logger->error('Error getting image Exif information: ' . $e->getMessage());
            }
        } else {
            $this->logger->info('Ignoring non-JPEG file when trying to set properties from EXIT.');
        }
    }
}

