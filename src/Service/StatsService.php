<?php

namespace App\Service;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use Carbon\CarbonInterval;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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

    /** @var EntityManager */
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

    public function getWanderStats(): array
    {
        $stats = $this->cache->get('wander_stats', function(ItemInterface $item) {
            $item->tag('stats');
            $wanderStats = $this->wanderRepository
                ->createQueryBuilder('w') 
                ->select('COUNT(w.id) as totalCount')
                ->addSelect('COUNT(w.title) as countWithTitle')
                ->addSelect('COUNT(w.description) as countWithDescription')
                //->addSelect('SUM(w.durationSeconds) as totalDuration')
                ->addSelect('COALESCE(SUM(w.distance), 0) as totalDistance')
                ->addSelect('COALESCE(SUM(w.cumulativeElevationGain), 0) as totalCumulativeElevationGain')
                ->getQuery()
                ->getOneOrNullResult();

            // Doctrine doesn't support calculating a difference
            // in seconds from two datetime values via ORM. Let's
            // go raw.
            $conn = $this->entityManager->getConnection();
            $sql = 'SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) FROM wander';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $seconds = $stmt->fetchOne();

            $interval = CarbonInterval::seconds($seconds)->cascade();

            $wanderStats['totalDuration'] = $interval;
            $wanderStats['totalDurationForHumans'] = $interval->forHumans([
                    'short' => true
                ]);
            
            $wanderStats['longestWanderDistance'] = $this->wanderRepository
                ->createQueryBuilder('w')
                ->select('w.id, w.distance')
                ->orderBy('w.distance', 'desc')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            
            $wanderStats['shortestWanderDistance'] = $this->wanderRepository
                ->createQueryBuilder('w')
                ->select('w.id, w.distance')
                ->orderBy('w.distance', 'asc')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $wanderStats['hasWanders'] = $wanderStats['totalCount'] > 0;
            return $wanderStats;
        });
        
        return $stats;
    }
}