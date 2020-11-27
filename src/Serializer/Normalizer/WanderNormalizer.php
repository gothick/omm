<?php

namespace App\Serializer\Normalizer;

use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class WanderNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        if (is_array($data)) {
            // TODO: We should probably do this with Packages, or something. I couldn't figure
            // it out. There's a Symfony\Component\Asset\Packages and presumably some way of
            // adding uploads/gpx to it, but I don't know what that is.
            $data['gpxFilename'] = '/uploads/gpx/' . $data['gpxFilename'];
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof \App\Entity\Wander;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
