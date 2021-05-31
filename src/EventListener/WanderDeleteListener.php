<?php

namespace App\EventListener;

use App\Entity\Wander;
use App\Service\GpxService;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class WanderDeleteListener
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var GpxService */
    private $gpxService;

    public function __construct(LoggerInterface $logger, GpxService $gpxService)
    {
        $this->logger = $logger;
        $this->gpxService = $gpxService;
    }

    public function preRemove(
            Wander $wander,
            /** @scrutinizer ignore-unused */ LifecycleEventArgs $event
        ): void
    {
        // If we're about to delete a wander, we want to remove it as a featuring
        // wander from the related Image first, otherwise we'll break referential
        // integrity.
        $image = $wander->getFeaturedImage();
        if ($image !== null) {
            $image->setFeaturingWander(null);
        }
    }

    public function postRemove(
            Wander $wander,
            /** @scrutinizer ignore-unused */ LifecycleEventArgs $event
        ): void
    {
        $path = $this->gpxService->getFullGpxFilePathFromWander($wander);
        if (!file_exists($path)) {
            $this->logger->debug("Could not find GPX file " . $path . " to remove");
            return;
        }

        $this->logger->debug("Removing GPX file " . $path . " on deletion of wander " . $wander->getId());
        unlink($path);
    }
}