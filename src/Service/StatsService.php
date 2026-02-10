<?php

namespace App\Service;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
    public function __construct(private readonly ImageRepository $imageRepository, private readonly WanderRepository $wanderRepository, private readonly TagAwareCacheInterface $cache, private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return array<mixed>
     */
    public function getImageStats(): array
    {
        return $this->cache->get('image_stats', function(ItemInterface $item) {
            $item->tag('stats');
            return $this->imageRepository
                ->createQueryBuilder('i')
                ->select('COUNT(i.id) as totalCount')
                ->addSelect('COUNT(i.latlng) as countWithCoords')
                ->addSelect('COUNT(i.title) as countWithTitle')
                ->addSelect('COUNT(i.description) as countWithDescription')
                ->getQuery()
                ->getSingleResult();
        });
    }

    /**
     * @return array<mixed>
     */
    public function getImageNeighbourhoodStats(): array
    {
        return $this->cache->get('image_neighbourhood_stats', function (ItemInterface $item) {
            $item->tag('stats');
            $stats = $this->imageRepository
                ->createQueryBuilder('i')
                ->select('i.neighbourhood')
                ->addSelect('COUNT(i) AS neighbourhoodCount')
                ->groupBy('i.neighbourhood')
                ->Where('i.neighbourhood IS NOT NULL')
                ->andWhere("i.neighbourhood != ''")
                ->OrderBy('i.neighbourhood')
                ->getQuery()
                ->getResult();
            return array_column($stats, 'neighbourhoodCount', 'neighbourhood');
        });
    }

    /**
     * @return array<mixed>
     */
    public function getWanderStats(): array
    {
        return $this->cache->get('wander_stats', function(ItemInterface $item) {
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
                'month',
                'MMM YYYY'
            );

            $wanderStats['yearlyStats'] = $this->getPeriodicStats(
                $overallTimeStats['firstWanderStartTime']->startOfYear(),
                $overallTimeStats['latestWanderStartTime']->startOfMonth(),
                'year',
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
        return [
            'firstWanderStartTime' => Carbon::parse($row['firstWanderStartTime']),
            'latestWanderStartTime' => Carbon::parse($row['latestWanderStartTime']),
            'totalDuration' => CarbonInterval::seconds((int) $row['totalDuration'])->cascade(),
            'averageDuration'=> CarbonInterval::seconds((int) $row['averageDuration'])->cascade()
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPeriodicStats(Carbon $startMonth, Carbon $endMonth, string $periodType, string $periodLabelFormat): array
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
                    COALESCE(SUM((SELECT COUNT(*) FROM image i WHERE (rating IS NULL or rating = 0) AND i.wander_id = w.id)), 0) AS rating_0_images,
                    COALESCE(SUM((SELECT COUNT(*) FROM image i WHERE rating = 1 AND i.wander_id = w.id)), 0) AS rating_1_images,
                    COALESCE(SUM((SELECT COUNT(*) FROM image i WHERE rating = 2 AND i.wander_id = w.id)), 0) AS rating_2_images,
                    COALESCE(SUM((SELECT COUNT(*) FROM image i WHERE rating = 3 AND i.wander_id = w.id)), 0) AS rating_3_images,
                    COALESCE(SUM((SELECT COUNT(*) FROM image i WHERE rating = 4 AND i.wander_id = w.id)), 0) AS rating_4_images,
                    COALESCE(SUM((SELECT COUNT(*) FROM image i WHERE rating = 5 AND i.wander_id = w.id)), 0) AS rating_5_images,
                    COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(w.end_time, w.start_time))), 0) AS total_duration_seconds,
                    COALESCE(AVG(TIME_TO_SEC(TIMEDIFF(w.end_time, w.start_time))), 0) AS average_duration_seconds
                FROM
                    wander w
                WHERE
                    w.start_time >= :start AND
                    w.start_time < :end';

        $stmt = $this->entityManager->getConnection()->prepare($sql);

        $periodicStats = [];

        $periodLengthMonths = $periodType === 'year' ? 12 : 1;

        for ($rangeStartMonth = $startMonth->copy(); $rangeStartMonth <= $endMonth; $rangeStartMonth->addMonths($periodLengthMonths)) {
            $rangeEndMonth = $rangeStartMonth->copy()->addMonths($periodLengthMonths);
            $stmt->bindValue("start", $rangeStartMonth);
            $stmt->bindValue("end", $rangeEndMonth);
            $result = $stmt->executeQuery();
            $row = $result->fetchAssociative();
            if ($row === false) {
                // It's entirely aggregated, so even if no rows match the WHERE there should always be a row
                // returned.
                throw new Exception("Expected to get a row back from the database no matter what with this query.");
            }
            $duration = CarbonInterval::seconds((int) $row['total_duration_seconds'])->cascade();
            $periodicStats[] = [
                'periodType' => $periodType,
                'periodStartDate' => new CarbonImmutable($rangeStartMonth),
                'periodEndDate' => new CarbonImmutable($rangeEndMonth->copy()->addDays(-1)),
                //'year' => $rangeStartMonth->year,
                //'month' => $rangeStartMonth->month,
                'periodLabel' => $rangeStartMonth->isoFormat($periodLabelFormat),
                'numberOfWanders' => (int) $row['number_of_wanders'],
                'totalDistance' => (float) $row['total_distance_metres'],
                'numberOfImages' => (int) $row['number_of_images'],
                'numberOfImagesByRating' => [
                    0 => (int) $row['rating_0_images'],
                    1 => (int) $row['rating_1_images'],
                    2 => (int) $row['rating_2_images'],
                    3 => (int) $row['rating_3_images'],
                    4 => (int) $row['rating_4_images'],
                    5 => (int) $row['rating_5_images'],
                ],
                'totalDurationInterval' => $duration,
                'totalDurationForHumans' => $duration->forHumans(['short' => true, 'options' => 0]),
                'averageDurationInterval' => CarbonInterval::seconds((int) $row['average_duration_seconds'])->cascade(),
            ];
        }
        return $periodicStats;
    }
}
