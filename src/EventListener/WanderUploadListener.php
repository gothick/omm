<?php

namespace App\EventListener;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class WanderUploadListener
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var ImageRepository $imageRepository */
    private $imageRepository;

    public function __construct(LoggerInterface $logger, ImageRepository $imageRepository)
    {
        $this->logger = $logger;
        $this->imageRepository = $imageRepository;
    }

    public function prePersist(Wander $wander, LifecycleEventArgs $event): void
    {
        // Do something
        $startTime = $wander->getStartTime();
        $endTime = $wander->getEndTime();

        if (isset($startTime, $endTime)) {
            $images = $this->imageRepository->findBetweenDates($startTime, $endTime);
            foreach ($images as $image) {
                $wander->addImage($image);
            }
        }
    }
}