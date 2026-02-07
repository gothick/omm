<?php

namespace App\MessageHandler;

use App\Message\RecogniseImage;
use App\Repository\ImageRepository;
use App\Service\ImageTaggingServiceInterface;
use Psr\Log\LoggerInterface;
use Symonfy\Component\Messeger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RecogniseImageHandler {

    /** @var ImageTaggingServiceInterface */
    private $imageTaggingService;

    /** @var ImageRepository */
    private $imageRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ImageTaggingServiceInterface $imageTaggingService,
        ImageRepository $imageRepository,
        LoggerInterface $logger)
    {
        $this->imageTaggingService = $imageTaggingService;
        $this->imageRepository = $imageRepository;
        $this->logger = $logger;
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
