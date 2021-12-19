<?php

namespace App\Service;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
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
                ->getSingleResult();
            return $imageStats;
        });
        return $stats;
    }

    /**
     * @return array<mixed>
     */
    public function getImageLocationStats(): array
    {
        return $this->cache->get('image_location_stats', function (ItemInterface $item) {
            $item->tag('stats');
            $stats = $this->imageRepository
                ->createQueryBuilder('i')
                ->select('i.location')
                ->addSelect('COUNT(i) AS locationCount')
                ->groupBy('i.location')
                ->Where('i.location IS NOT NULL')
                ->OrderBy('i.location')
                ->getQuery()
                ->getResult();
            return array_column($stats, 'locationCount', 'location');
        });
    }

    /**
     * @return array<mixed>
     */
    public function getWanderStats(): array
    {
        $stats = $this->cache->get('wander_stats', function(ItemInterface $item) {
            $item->tag('stats');

            $wanderStats = $this->getGeneralWanderStats();
            $overallTimeStats = $this->getOverallTimeStats();

            $wanderStats += $overallTimeStats;

            $wanderStats['totalDurationForHumans'] = $wanderStats['totalDuration']
                ->forHumans(['short' => true, 'options' => 0]); // https://github.com/briannesbitt/Carbon/issues/2035
            $wanderStats['averageDurationForHumans'] = $wanderStats['averageDuration']
                ->forHumans(['short' => true, 'options' => 0]); // https://github.com/briannesbitt/Carbon/issues/2035

            // Distances
            $wanderStats['shortestWanderDistance'] = $this->wanderRepository->findShortest();
            $wanderStats['longestWanderDistance'] = $this->wanderRepository->findLongest();
            $wanderStats['averageWanderDistance'] = $this->wanderRepository->findAverageDistance();

            $wanderStats['monthlyStats'] = $this->getPeriodicStats(
                $overallTimeStats['firstWanderStartTime']->startOfMonth(),
                $overallTimeStats['latestWanderStartTime']->startOfMonth(),
                1,
                'MMM YYYY'
            );

            $wanderStats['yearlyStats'] = $this->getPeriodicStats(
                $overallTimeStats['firstWanderStartTime']->startOfYear(),
                $overallTimeStats['latestWanderStartTime']->startOfMonth(),
                12,
                'YYYY'
            );

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

    /**
     * @return array<string, mixed>
     */
    private function getGeneralWanderStats(): array
    {
        // General statistics
        $wanderStats = $this->wanderRepository
            ->createQueryBuilder('w')
            ->select('COUNT(w.id) as totalCount')
            ->addSelect('COUNT(w.title) as countWithTitle')
            ->addSelect('COUNT(w.description) as countWithDescription')
            ->addSelect('COALESCE(SUM(w.distance), 0) as totalDistance')
            ->addSelect('COALESCE(SUM(w.cumulativeElevationGain), 0) as totalCumulativeElevationGain')
            ->getQuery()
            ->getSingleResult();

        $wanderStats['hasWanders'] = $wanderStats['totalCount'] > 0;
        return $wanderStats;
    }
    /**
     * @return array<string, mixed>
     */
    private function getOverallTimeStats(): array
    {
        // Doctrine doesn't support calculating a difference
        // in seconds from two datetime values via ORM. Let's
        // go raw. Seeing as we're aggregating over all wanders
        // and want to process the results into Carbon dates,
        // we might as well also get the earliest and latest
        // wanders, too. These will also be helpful for our monthly
        // chart where we don't want to skip missing months.
        $conn = $this->entityManager->getConnection();
        $sql = 'SELECT
                    COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) AS totalDuration,
                    COALESCE(AVG(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) AS averageDuration,
                    MIN(start_time) AS firstWanderStartTime,
                    MAX(start_time) AS latestWanderStartTime
                FROM wander
            ';
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $row = $result->fetchAssociative();

        if ($row === false) {
            throw new Exception("Got no results when finding duration stats.");
        }

        $overallTimeStats = [
            'firstWanderStartTime' => Carbon::parse($row['firstWanderStartTime']),
            'latestWanderStartTime' => Carbon::parse($row['latestWanderStartTime']),
            'totalDuration' => CarbonInterval::seconds($row['totalDuration'])->cascade(),
            'averageDuration'=> CarbonInterval::seconds($row['averageDuration'])->cascade()
        ];
        return $overallTimeStats;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPeriodicStats(Carbon $startMonth, Carbon $endMonth, int $periodLengthMonths, string $periodLabelFormat): array
    {
        // Stats per month or year. It would be most efficient to write some complicated SQL query
        // that groups the lot together, including filling in months with missing data using some kind
        // of row generator or date dimension table, but frankly this is still fast enough,
        // especially as it's cached and invalidated quite sensibly.
        $sql = 'SELECT
                    COUNT(*) AS number_of_wanders,
                    COALESCE(SUM(w.distance), 0) AS total_distance_metres,
                    COALESCE(SUM(w.distance), 0) / 1000.0 AS total_distance_km,
                    COALESCE(SUM((SELECT COUNT(*) FROM image i WHERE i.wander_id = w.id)), 0) AS number_of_images,
                    COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(w.end_time, w.start_time))), 0) AS total_duration_seconds,
                    COALESCE(AVG(TIME_TO_SEC(TIMEDIFF(w.end_time, w.start_time))), 0) AS average_duration_seconds
                FROM
                    wander w
                WHERE
                    w.start_time >= :start AND
                    w.start_time < :end';

        $stmt = $this->entityManager->getConnection()->prepare($sql);

        $periodicStats = [];

        for ($rangeStartMonth = $startMonth->copy(); $rangeStartMonth <= $endMonth; $rangeStartMonth->addMonths($periodLengthMonths)) {
            $rangeEndMonth = $rangeStartMonth->copy()->addMonths($periodLengthMonths);
            $result = $stmt->executeQuery([
                'start' => $rangeStartMonth,
                'end' => $rangeEndMonth
            ]);
            $row = $result->fetchAssociative();
            if ($row === false) {
                // It's entirely aggregated, so even if no rows match the WHERE there should always be a row
                // returned.
                throw new Exception("Expected to get a row back from the database no matter what with this query.");
            }
            $duration = CarbonInterval::seconds($row['total_duration_seconds'])->cascade();
            $periodicStats[] = [
                'periodStartDate' => $rangeStartMonth,
                'periodEndDate' => $rangeEndMonth,
                'periodLabel' => $rangeStartMonth->isoFormat($periodLabelFormat),
                'starYear' => $rangeStartMonth->year,
                'startMonth' => $rangeStartMonth->month,
                'numberOfWanders' => (int) $row['number_of_wanders'],
                'totalDistance' => (float) $row['total_distance_metres'],
                'numberOfImages' => (int) $row['number_of_images'],
                'totalDurationInterval' => $duration,
                'totalDurationForHumans' => $duration->forHumans(['short' => true, 'options' => 0]),
                'averageDurationInterval' => CarbonInterval::seconds($row['average_duration_seconds'])->cascade(),
            ];
        }
        return $periodicStats;
    }
}
