<?php

namespace App\MessageHandler;

use App\Message\RecogniseImage;
use App\Repository\ImageRepository;
use App\Service\ImageTaggingServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RecogniseImageHandler {

    public function __construct(private readonly ImageTaggingServiceInterface $imageTaggingService, private readonly ImageRepository $imageRepository, private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(RecogniseImage $recogniseImage): void
    {
        $this->logger->debug('RecogniseImageHandler invoked');
        $imageid = $recogniseImage->getImageId();
        if ($imageid == null) {
            throw new \RuntimeException("Image ID from RecogniseImage message is null");
        }
        $image = $this->imageRepository->find($imageid);
        if ($image == null) {
            throw new \RuntimeException("Image with ID $imageid not found");
        }
        $this->logger->debug("RecogniseImageHandler found image with id " . $image->getId());
        $this->logger->debug("RecogniseImageHandler calling image tagger");
        $this->imageTaggingService->tagImage($image, $recogniseImage->getOverwrite());
    }
}
