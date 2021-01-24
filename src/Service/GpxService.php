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
    /** @var array */
    private $homebaseCoords;

    public function __construct(string $gpxDirectory, LoggerInterface $logger, array $homebaseCoords)
    {
        $this->phpGpx = new phpGPX();
        $this->gpxDirectory = $gpxDirectory;
        $this->logger = $logger;
        $this->homebaseCoords = $homebaseCoords;
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

    private function updateGeneralStats(string $gpxxml, Wander $wander): void
    {
        $gpx = $this->phpGpx->parse($gpxxml);
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
                //$this->logger->debug("Couldn't set extended GPX property on wander: " . $e->getMessage());
                throw new Exception("Couldn't set standard GPX stats properties on wander.", 0, $e);
            }
        }
    }

    /**
     * Update centroid and related angle from "home base" to the centroid,
     * using geoPHP. geoPHP is somewhat overkill and annoyingly old-school
     * (e.g. in the global namespace) but it's powerful and we may end
     * up using it elsewhere.
     */
    private function updateCentroid(string $gpxxml, Wander $wander): void
    {
        // Centroid, updated using geoPHP
        $gpx = \geoPHP::load($gpxxml, 'gpx'); // It's horrible old code, in the global namespace
        $centroid = $gpx->getCentroid();
        $wander->setCentroid([$centroid->y(), $centroid->x()]);
        $angle = $this->compass((
            $centroid->x() - $this->homebaseCoords[1]),
            ($centroid->y() - $this->homebaseCoords[0])
        );
        $wander->setAngleFromHome($angle);
    }

    /**
     * Translate co-ordinates relative to 0, 0 into a compass direction in degrees.
     */
    public function compass(float $x, float $y): float
    {
        // https://www.php.net/manual/en/function.atan2.php#88119
        if($x==0 AND $y==0){ return 0; } // ...or return 360
        return ($x < 0)
            ? rad2deg(atan2($x,$y)) + 360
            : rad2deg(atan2($x,$y));
    }

    public function updateWanderStatsFromGpx(Wander $wander): void
    {
        $filename = $wander->getGpxFilename();
        if (isset($filename))
        {
            // Basic stats, updated using phpGpx
            $gpxxml = file_get_contents($this->getFullGpxFilePathFromWander($wander));
            if ($gpxxml === false) {
                throw new Exception("Error loading GPX file for wander " . $wander->getId());
            }
            $this->updateGeneralStats($gpxxml, $wander);
            $this->updateCentroid($gpxxml, $wander);
        }
    }
}