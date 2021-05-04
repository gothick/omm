<?php

namespace App\MessageHandler;

use App\Message\RecogniseImage;
use App\Repository\ImageRepository;
use App\Service\ImageTaggingServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RecogniseImageHandler implements MessageHandlerInterface {

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
        $image = $this->imageRepository->find($recogniseImage->getImageId());
        $this->logger->debug("RecogniseImageHandler found image with id " . $image->getId());
        if ($image !== null) {
            $this->logger->debug("RecogniseImageHandler calling image tagger");
            $this->imageTaggingService->tagImage($image, $recogniseImage->getOverwrite());
        }
    }
}