<?php

namespace App\Service;

use App\Entity\Wander;
use Exception;
use phpGPX\phpGPX;
use Psr\Log\LoggerInterface;

class GpxService
{
    /** @var phpGPX */
    private $phpGpx;
    /** @var string */
    private $gpxDirectory;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $gpxDirectory, LoggerInterface $logger)
    {
        $this->phpGpx = new phpGPX();
        $this->gpxDirectory = $gpxDirectory;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function getFullGpxFilePathFromWander(Wander $wander):string
    {
        $filename = $wander->getGpxFilename();
        if (!$filename) {
            throw new \Exception("No GPX file path found in wander.");
        }
        return $this->gpxDirectory . '/' . $filename;
    }

    public function updateWanderStatsFromGpx(Wander $wander)
    {
        $filename = $wander->getGpxFilename();
        if (isset($filename))
        {

            $gpx = $this->phpGpx->load($this->getFullGpxFilePathFromWander($wander));

            // TODO: Cope with mutliple tracks in a file? I don't think
            // we've done that often, if ever.
            foreach ($gpx->tracks as $track)
            {
                $stats = $track->stats;
                // These are the only essentials
                $wander->setStartTime($stats->startedAt);
                $wander->setEndTime($stats->finishedAt);
                try {
                    $wander->setDistance($stats->distance);
                    $wander->setAvgPace($stats->averagePace);
                    $wander->setAvgSpeed($stats->averageSpeed);
                    $wander->setMaxAltitude($stats->maxAltitude);
                    $wander->setMinAltitude($stats->minAltitude);
                    $wander->setCumulativeElevationGain($stats->cumulativeElevationGain);
                }
                catch(Exception $e) {
                    $this->logger->debug("Couldn't set extended GPX property on wander: " . $e->getMessage());
                }
            }
        }
    }
}