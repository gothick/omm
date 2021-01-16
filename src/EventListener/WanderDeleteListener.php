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

    public function postRemove(
            Wander $wander,
            /** @scrutinizer ignore-unused */ LifecycleEventArgs $event
        ): void
    {
        $path = $this->gpxService->getFullGpxFilePathFromWander($wander);
        if (file_exists($path)) {
            $this->logger->debug("Removing GPX file " . $path . " on deletion of wander " . $wander->getId());
            unlink($path);
        } else {
            $this->logger->debug("Could not find GPX file " . $path . " to remove");
        }
    }
}