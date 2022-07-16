<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Entity\Wander;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class StatsCacheClearer implements EventSubscriber
{
    /** @var TagAwareCacheInterface */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
            TagAwareCacheInterface $cache,
            LoggerInterface $logger
        )
    {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->clearCache($args);
    }
    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->clearCache($args);
    }
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->clearCache($args);
    }


    // https://symfony.com/doc/current/doctrine/events.html#doctrine-lifecycle-listeners
    public function clearCache(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Wander || $entity instanceof Image) {
            $this->logger->debug('Clearing statistics cache on Doctrine lifecycle event');
            $this->cache->invalidateTags(['stats']);
        }
    }

}
