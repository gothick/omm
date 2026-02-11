<?php

namespace App\MessageHandler;

use App\Message\GeolocateImage;
use App\Repository\ImageRepository;
use App\Service\LocationTaggingServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class GeolocateImageHandler {

    public function __construct(private readonly LocationTaggingServiceInterface $locationTaggingService, private readonly ImageRepository $imageRepository, private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(GeolocateImage $geolocateImage): void
    {
        $this->logger->debug('GeolocateImageHandler invoked');
        $image = $this->imageRepository->find($geolocateImage->getImageId());
        if ($image !== null) {
            $imageid = $image->getId();
            if ($imageid === null) {
                throw new UnrecoverableMessageHandlingException("Image has no ID");
            }

            $this->logger->debug("GeolocateImageHandler calling location tagger for image $imageid");
            $this->locationTaggingService->tagImage($image, $geolocateImage->getOverwrite());
        }
    }
}
