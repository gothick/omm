<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Service\ImageService;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ImageCalculatedFieldSetterListener
{
    public function __construct(private readonly LoggerInterface $logger, private readonly ImageService $imageService)
    {
    }

    // Mostly we want to set these when they're loaded from the database
    public function postLoad(Image $image): void
    {
        $this->imageService->setCalculatedImageUris($image);
    }
    // But also it's helpful to have them available just after we've created
    // a new Image, so we can return them as part of the JSON response.
    public function postPersist(Image $image): void
    {
        $this->imageService->setCalculatedImageUris($image);
    }
}