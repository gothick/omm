<?php

namespace App\Service;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use Carbon\Carbon;
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
            $overallTimeStats = $result->fetchAssociative();

            if ($overallTimeStats === false) {
                throw new Exception("Got no results when finding duration stats.");
            }

            $wanderStats['totalDuration'] = CarbonInterval::seconds($overallTimeStats['totalDuration'])->cascade();
            $wanderStats['averageDuration'] = CarbonInterval::seconds($overallTimeStats['averageDuration'])->cascade();
            $firstWanderStartTime = Carbon::parse($overallTimeStats['firstWanderStartTime']);
            $latestWanderStartTime = Carbon::parse($overallTimeStats['latestWanderStartTime']);
            $wanderStats['firstWanderStartTime'] = $firstWanderStartTime;
            $wanderStats['latestWanderStartTime'] = $latestWanderStartTime;

            $wanderStats['totalDurationForHumans'] = $wanderStats['totalDuration']
                ->forHumans(['short' => true, 'options' => 0]); // https://github.com/briannesbitt/Carbon/issues/2035
            $wanderStats['averageDurationForHumans'] = $wanderStats['averageDuration']
                ->forHumans(['short' => true, 'options' => 0]); // https://github.com/briannesbitt/Carbon/issues/2035

            // Distances
            $wanderStats['shortestWanderDistance'] = $this->wanderRepository->findShortest();
            $wanderStats['longestWanderDistance'] = $this->wanderRepository->findLongest();
            $wanderStats['averageWanderDistance'] = $this->wanderRepository->findAverageDistance();

            // Stats per month. It would be most efficient to write some complicated SQL query that
            // groups the lot together, including filling in months with missing data using some kind
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

            $stmt = $conn->prepare($sql);

            $monthlyStats = [];

            $firstWanderMonth = $firstWanderStartTime->startOfMonth();
            $latestWanderMonth = $latestWanderStartTime->startOfMonth();

            for ($currMonth = $firstWanderMonth; $currMonth <= $latestWanderMonth; $currMonth->addMonths(1)) {
                $nextMonth = $currMonth->copy()->addMonths(1);
                $result = $stmt->executeQuery([
                    'start' => $currMonth,
                    'end' => $nextMonth
                ]);
                $row = $result->fetchAssociative();
                if ($row === false) {
                    // It's entirely aggregated, so even if no rows match the WHERE there should always be a row
                    // returned.
                    throw new \Exception("Expected to get a row back from the database no matter what with this query.");
                }
                $monthlyStats[] = [
                    'firstOfMonthDate' => $currMonth,
                    'monthLabel' => $currMonth->isoFormat('MMM YYYY'),
                    'year' => $currMonth->year,
                    'month' => $currMonth->month,
                    'numberOfWanders' => (int) $row['number_of_wanders'],
                    'totalDistanceMetres' => (float) $row['total_distance_metres'],
                    'numberOfImages' => (int) $row['number_of_images'],
                    'totalDurationInterval' => CarbonInterval::seconds($row['total_duration_seconds'])->cascade(),
                    'averageDurationInterval' => CarbonInterval::seconds($row['average_duration_seconds'])->cascade(),
                ];
            }

            $wanderStats['monthlyStats'] = $monthlyStats;

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
