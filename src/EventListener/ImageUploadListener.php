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
    /** @var LoggerInterface */
    private $logger;

    /** @var WanderRepository */
    private $wanderRepository;

    /** @var ImageService */
    private $imageService;

    public function __construct(LoggerInterface $logger, WanderRepository $wanderRepository, ImageService $imageService)
    {
        $this->logger = $logger;
        $this->wanderRepository = $wanderRepository;
        // TODO Take this back out once we're finished playing with it.
        $this->imageService = $imageService;
    }

    public function onVichUploaderPostUpload(Event $event)
    {
        /** @var \App\Entity\Image $object */
        $object = $event->getObject();
        if ($object instanceof Image) {
            $this->imageService->setPropertiesFromEXIF($object);
        }
        else {
            $this->logger->error("Hey, did you start to upload things that aren't images using Vich?");
        }
    }
}