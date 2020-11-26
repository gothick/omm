<?php

namespace App\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\Wander;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class WanderApiNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    private $assetExtension;
    private $router;

    public function __construct(NormalizerInterface $decorated, AssetExtension $assetExtension)
    {
        if (!$decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class));
        }

        $this->decorated = $decorated;
        $this->assetExtension = $assetExtension;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Wander;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->decorated->normalize($object, $format, $context);
        if (is_array($data)) {
            $data['gpxFilename'] = $this->assetExtension->getAssetUrl('uploads/gpx/') . $data['gpxFilename'];
        }

        return $data;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->decorated->denormalize($data, $class, $format, $context);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        if($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}