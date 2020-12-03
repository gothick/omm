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
    /** @var LoggerInterface */
    private $logger;

    /** @var ImageService */
    private $imageService;

    public function __construct(LoggerInterface $logger, ImageService $imageService)
    {
        $this->logger = $logger;
        $this->imageService = $imageService;
    }

    public function postLoad(Image $image): void
    {
        $uris = $this->imageService->getImageUris($image);
        $image->setImageUri($uris['imageUri']);
        $image->setMarkerImageUri($uris['markerImageUri']);
        $image->setMediumImageUri($uris['mediumImageUri']);
        $image->setImageEntityAdminUri($uris['imageEntityAdminUri']);
    }
}