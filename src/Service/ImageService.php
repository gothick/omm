<?php 

namespace App\Service;

use App\Entity\Image;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ImageService {

    /** @var UploaderHelper */
    private $uploaderHelper;

    /** @var CacheManager */
    private $imagineCacheManager;

    /** @var UrlGeneratorInterface */
    private $router;

    public function __construct(
        UploaderHelper $uploaderHelper, 
        CacheManager $imagineCacheManager,
        UrlGeneratorInterface $router)
    {
        $this->uploaderHelper = $uploaderHelper;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->router = $router;
    }

    public function getImageUris(Image $image): array
    {
        $image_asset_path = $this->uploaderHelper->asset($image);
        $urls = [];
        $urls['imageUri'] = $image_asset_path;
        $urls['markerImageUri'] = $this->imagineCacheManager->getBrowserPath($image_asset_path, 'marker_thumb');
        $urls['mediumImageUri'] = $this->imagineCacheManager->getBrowserPath($image_asset_path, 'square_thumb_600');
        $urls['imageEntityAdminUri'] = $this->router->generate('admin_image_show', ['id' => $image->getId()]);
        return $urls;
    }
}