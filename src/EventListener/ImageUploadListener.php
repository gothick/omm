<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Repository\WanderRepository;
use App\Service\ImageService;
use Exception;
use PHPExif\Reader\Reader;
use Psr\Log\LoggerInterface;
use Vich\UploaderBundle\Event\Event;

class ImageUploadListener
{
    public function __construct(private readonly LoggerInterface $logger, private readonly WanderRepository $wanderRepository, private readonly ImageService $imageService)
    {
    }

    public function onVichUploaderPostUpload(Event $event)
    {
        $object = $event->getObject();
        if (!$object instanceof Image) {
            throw new \Exception("Vich upload listener invoked on non-image object.");
        }
        $this->imageService->setPropertiesFromEXIF($object);
    }
}
