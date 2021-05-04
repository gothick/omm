<?php

namespace App\MessageHandler;

use App\Message\RecogniseImage;
use App\Repository\ImageRepository;
use App\Service\ImageTaggingServiceInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RecogniseImageHandler implements MessageHandlerInterface {

    /** @var ImageTaggingServiceInterface */
    private $imageTaggingService;

    /** @var ImageRepository */
    private $imageRepository;

    public function __construct(
        ImageTaggingServiceInterface $imageTaggingService,
        ImageRepository $imageRepository)
    {
        $this->imageTaggingService = $imageTaggingService;
        $this->imageRepository = $imageRepository;
    }

    public function __invoke(RecogniseImage $recogniseImage): void
    {
        $image = $this->imageRepository->find($recogniseImage->getImageId());
        if ($image !== null) {
            $this->imageTaggingService->tagImage($image, $recogniseImage->getOverwrite());
        }
    }
}