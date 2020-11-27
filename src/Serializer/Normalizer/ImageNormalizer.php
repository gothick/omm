<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ImageNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;
    private $uploaderHelper;

    public function __construct(ObjectNormalizer $normalizer, UploaderHelper $uploaderHelper)
    {
        $this->normalizer = $normalizer;
        $this->uploaderHelper = $uploaderHelper;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // Here: add, edit, or delete some data
        $data['imageUri'] = $this->uploaderHelper->asset($object); 

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
