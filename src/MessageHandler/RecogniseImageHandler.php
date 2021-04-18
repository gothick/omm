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
            try {
                $this->imageTaggingService->tagImage($image, $recogniseImage->getOverwrite());
            }
            catch(\Exception $e) {
                // Later on I might get cleverer, but for now let's just
                // mark everything as non-retryable so we don't annoy
                // Imagga too much, given that we've run out of free
                // recognitions for the month. We can always manually
                // re-try sending queued messages.
                throw new UnrecoverableMessageHandlingException(
                    "Imagga service threw exception. Marking no retries for now.",
                    $e->getCode(),
                    $e
                );
            }
        }
    }
}