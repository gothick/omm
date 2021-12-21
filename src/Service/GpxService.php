<?php

namespace App\Service;

use App\Entity\Wander;
use Exception;
use Gothick\Geotools\Polyline;
use Gothick\Geotools\PolylineGoogleEncodedFormatter;
use Gothick\Geotools\PolylineRdpSimplifier;
use phpGPX\phpGPX;
use phpGPX\Models\Stats;
use Psr\Log\LoggerInterface;

class GpxService
{
    /** @var phpGPX */
    private $phpGpx;
    /** @var string */
    private $gpxDirectory;
    /** @var LoggerInterface */
    private $logger;
    /** @var array<float> */
    private $homebaseCoords;
    /** @var int */
    private $wanderSimplifierEpsilonMetres;

    /**
     * @param array<float> $homebaseCoords
     */
    public function __construct(
        string $gpxDirectory,
        LoggerInterface $logger,
        array $homebaseCoords,
        int $wanderSimplifierEpsilonMetres
    ) {
        $this->phpGpx = new phpGPX();
        $this->gpxDirectory = $gpxDirectory;
        $this->logger = $logger;
        $this->homebaseCoords = $homebaseCoords;
        $this->wanderSimplifierEpsilonMetres = $wanderSimplifierEpsilonMetres;
    }

    /**
     * @throws \Exception
     */
    public function getFullGpxFilePathFromWander(Wander $wander): string
    {
        $filename = $wander->getGpxFilename();
        if (!$filename) {
            throw new \Exception("No GPX file path found in wander.");
        }
        return $this->getFullGpxFilePathFromFilename($filename);
    }

    public function getFullGpxFilePathFromFilename(string $filename): string
    {
        return $this->gpxDirectory . '/' . $filename;
    }

    public function getGpxStringFromFilename(string $filename): string
    {
        $gpx = \file_get_contents($this->getFullGpxFilePathFromFilename($filename));
        if ($gpx === false) {
            throw new Exception("Couldn't read GPX file from $filename");
        }
        return $gpx;
    }

    private function updateGeneralStats(string $gpxxml, Wander $wander): void
    {
        $gpx = $this->phpGpx->parse($gpxxml);
        // TODO: Cope with multiple tracks in a file? I don't think
        // we've done that often, if ever.
        foreach ($gpx->tracks as $track)
        {
            /** @var Stats $stats */
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
     * though now we're using our own library the definition of "centroid"
     * is currently "the average of the latitude and longitude values",
     * which is close enough for rock & roll.
     */
    private function updateCentroid(string $gpxxml, Wander $wander): void
    {
        $polyline = Polyline::fromGpxData($gpxxml);
        $centroid = $polyline->getCentroid();
        $wander->setCentroid([$centroid->getLat(), $centroid->getLng()]);
        $angle = $this->compass((
            $centroid->getLng() - $this->homebaseCoords[1]),
            ($centroid->getLat() - $this->homebaseCoords[0])
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

    public function gpxToGooglePolyline(string $gpx, float $epsilon = 3): string
    {
        $polyline = Polyline::fromGpxData($gpx);
        $simplifier = new PolylineRdpSimplifier($epsilon);
        $simplifiedPolyline = $simplifier->ramerDouglasPeucker($polyline);
        $formatter = new PolylineGoogleEncodedFormatter();
        return $formatter->format($simplifiedPolyline);
    }

    public function updateWanderFromGpx(Wander $wander): void
    {
        $filename = $wander->getGpxFilename();
        if (isset($filename))
        {
            $gpxxml = $this->getGpxStringFromFilename($filename);
            $wander->setGooglePolyline($this->gpxToGooglePolyline($gpxxml, $this->wanderSimplifierEpsilonMetres));
            $this->updateGeneralStats($gpxxml, $wander);
            $this->updateCentroid($gpxxml, $wander);
        }
    }
}