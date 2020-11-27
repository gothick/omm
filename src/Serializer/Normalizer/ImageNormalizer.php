<?php

namespace App\Serializer\Normalizer;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
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

    public function __construct(
        ObjectNormalizer $normalizer, 
        UploaderHelper $uploaderHelper,
        CacheManager $imagineCacheManager)
    {
        $this->normalizer = $normalizer;
        $this->uploaderHelper = $uploaderHelper;
        $this->imagineCacheManager = $imagineCacheManager;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        $image_asset_path = $this->uploaderHelper->asset($object); 
        $data['imageUri'] = $image_asset_path;
        $data['markerImageUri'] = $this->imagineCacheManager->getBrowserPath($image_asset_path, 'marker_thumb');
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
