<?php

namespace App\Service;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use Carbon\CarbonInterval;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class StatsService
{
    /** @var ImageRepository */
    private $imageRepository;

    /** @var WanderRepository */
    private $wanderRepository;

    /** @var TagAwareCacheInterface */
    private $cache;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ImageRepository $imageRepository,
        WanderRepository $wanderRepository,
        TagAwareCacheInterface $cache,
        EntityManagerInterface $entityManager)
    {
        $this->imageRepository = $imageRepository;
        $this->wanderRepository = $wanderRepository;
        $this->cache = $cache;
        $this->entityManager = $entityManager;
    }

    /**
     * @return array<mixed>
     */
    public function getImageStats(): array
    {
        $stats = $this->cache->get('image_stats', function(ItemInterface $item) {
            $item->tag('stats');
            $imageStats = $this->imageRepository
                ->createQueryBuilder('i')
                ->select('COUNT(i.id) as totalCount')
                ->addSelect('COUNT(i.latlng) as countWithCoords')
                ->addSelect('COUNT(i.title) as countWithTitle')
                ->addSelect('COUNT(i.description) as countWithDescription')
                ->getQuery()
                ->getOneOrNullResult();
            return $imageStats;
        });
        return $stats;
    }

    /**
     * @return array<mixed>
     */
    public function getWanderStats(): array
    {
        $stats = $this->cache->get('wander_stats', function(ItemInterface $item) {
            $item->tag('stats');

            // General statistics
            $wanderStats = $this->wanderRepository
                ->createQueryBuilder('w')
                ->select('COUNT(w.id) as totalCount')
                ->addSelect('COUNT(w.title) as countWithTitle')
                ->addSelect('COUNT(w.description) as countWithDescription')
                ->addSelect('COALESCE(SUM(w.distance), 0) as totalDistance')
                ->addSelect('COALESCE(SUM(w.cumulativeElevationGain), 0) as totalCumulativeElevationGain')
                ->getQuery()
                ->getOneOrNullResult();

            $wanderStats['hasWanders'] = $wanderStats['totalCount'] > 0;

            // Durations
            // Doctrine doesn't support calculating a difference
            // in seconds from two datetime values via ORM. Let's
            // go raw.
            $conn = $this->entityManager->getConnection();
            $sql = 'SELECT
                        COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) AS totalDuration,
                        COALESCE(AVG(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) AS averageDuration
                    FROM wander
                ';
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery();
            $durations = $result->fetchAssociative();

            if ($durations === false) {
                throw new Exception("Got no results when finding duration stats.");
            }

            $wanderStats['totalDuration'] = CarbonInterval::seconds($durations['totalDuration'])->cascade();
            $wanderStats['averageDuration'] = CarbonInterval::seconds($durations['averageDuration'])->cascade();

            $wanderStats['totalDurationForHumans'] = $wanderStats['totalDuration']
                ->forHumans(['short' => true, 'options' => 0]); // https://github.com/briannesbitt/Carbon/issues/2035
            $wanderStats['averageDurationForHumans'] = $wanderStats['averageDuration']
                ->forHumans(['short' => true, 'options' => 0]); // https://github.com/briannesbitt/Carbon/issues/2035

            // Distances
            $wanderStats['shortestWanderDistance'] = $this->wanderRepository->findShortest();
            $wanderStats['longestWanderDistance'] = $this->wanderRepository->findLongest();
            $wanderStats['averageWanderDistance'] = $this->wanderRepository->findAverageDistance();

            // Specialist stuff
            $qb = $this->wanderRepository
                ->createQueryBuilder('w');
            $wanderStats['imageProcessingBacklog'] = $this->wanderRepository
                ->addWhereHasImages($qb, false)
                ->select('COUNT(w.id)')
                ->getQuery()
                ->getSingleScalarResult();

            return $wanderStats;
        });

        return $stats;
    }
}
