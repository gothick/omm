<?php

namespace App\Service;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
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

    public function __construct(ImageRepository $imageRepository, WanderRepository $wanderRepository, TagAwareCacheInterface $cache)
    {
        $this->imageRepository = $imageRepository;
        $this->wanderRepository = $wanderRepository;
        $this->cache = $cache;
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
                ->addSelect('SUM(w.duration) as totalDuration')
                ->addSelect('SUM(w.distance) as totalDistance')
                ->addSelect('SUM(w.cumulativeElevationGain) as totalCumulativeElevationGain')
                ->getQuery()
                ->getOneOrNullResult();
            
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
            return $wanderStats;
        });
        
        return $stats;
    }
}