<?php

namespace App\EventListener;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class WanderUploadListener
{
    public function __construct(private readonly LoggerInterface $logger, private readonly ImageRepository $imageRepository)
    {
    }

    public function prePersist(Wander $wander, /** @scrutinizer ignore-unused */ LifecycleEventArgs $event): void
    {
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