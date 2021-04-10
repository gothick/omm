<?php

namespace App\MessageHandler;

use App\Message\RecogniseImage;
use App\Repository\ImageRepository;
use App\Service\ImaggaService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RecogniseImageHandler implements MessageHandlerInterface {

    /** @var ImaggaService */
    private $imaggaService;

    /** @var ImageRepository */
    private $imageRepository;

    public function __construct(
        ImaggaService $imaggaService,
        ImageRepository $imageRepository)
    {
        $this->imaggaService = $imaggaService;
        $this->imageRepository = $imageRepository;
    }

    public function __invoke(RecogniseImage $recogniseImage): void
    {
        $image = $this->imageRepository->find($recogniseImage->getImageId());
        if ($image !== null) {
            $this->imaggaService->tagImage($image, $recogniseImage->getOverwrite());
        }
    }
}