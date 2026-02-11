<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Entity\Wander;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postUpdate)]
class StatsCacheClearer
{
    public function __construct(private readonly TagAwareCacheInterface $cache, private readonly LoggerInterface $logger)
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->clearCache($args);
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->clearCache($args);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->clearCache($args);
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function clearCache(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Wander || $entity instanceof Image) {
            $this->logger->debug('Clearing statistics cache on Doctrine lifecycle event');
            $this->cache->invalidateTags(['stats']);
        }
    }
}
