<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Entity\Wander;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class StatsCacheClearer
{
    /** @var TagAwareCacheInterface */
    private $cache;

    public function __construct(TagAwareCacheInterface $cache)
    {
        $this->cache = $cache;    
    }

    // https://symfony.com/doc/current/doctrine/events.html#doctrine-lifecycle-listeners
    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Wander || $entity instanceof Image) {
            $this->cache->invalidateTags(['stats']);
        }
    }
}
