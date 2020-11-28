<?php

namespace App\Serializer\Normalizer;

use App\Entity\Image;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ImageNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;
    /** @var UploaderHelper */
    private $uploaderHelper;
    /** @var CacheManager */
    private $imagineCacheManager;
    /** @var UrlGeneratorInterface */
    private $router;

    public function __construct(
        ObjectNormalizer $normalizer, 
        UploaderHelper $uploaderHelper,
        CacheManager $imagineCacheManager,
        UrlGeneratorInterface $router)
    {
        $this->normalizer = $normalizer;
        $this->uploaderHelper = $uploaderHelper;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->router = $router;
    }

    public function normalize($object, $format = null, array $context = []): array
    {   
        $data = $this->normalizer->normalize($object, $format, $context);

        $image_asset_path = $this->uploaderHelper->asset($object); 
        $data['imageUri'] = $image_asset_path;
        $data['markerImageUri'] = $this->imagineCacheManager->getBrowserPath($image_asset_path, 'marker_thumb');
        $data['mediumImageUri'] = $this->imagineCacheManager->getBrowserPath($image_asset_path, 'square_thumb_600');
        $data['imageEntityAdminUrl'] = $this->router->generate('image_show', ['id' => $object->getId()]);
        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof \App\Entity\Image;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
