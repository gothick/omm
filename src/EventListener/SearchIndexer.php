<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Service\ImageService;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;

/**
 * Indexing when any given Wander is updated is handled by foselastica listening
 * to Doctrine events and persisting as necessary. However, our index has a nested
 * index on the Wander's images, and we need to handle triggering a re-index
 * whenver any child Image has changed.
 */
class SearchIndexer
{
    public function __construct(private readonly LoggerInterface $logger, private readonly ObjectPersisterInterface $wanderPersister)
    {
        $this->logger->debug("Constructing SearchIndexer Doctrine Entity Listener");
    }

    public function postPersist(Image $image): void
    {
        $this->logger->debug("In SeachIndexer PostPersist method");
        $this->updateRelatedWander($image);
    }

    public function postUpdate(Image $image): void
    {
        $this->logger->debug("In SeachIndexer PostUpdate method");
        $this->updateRelatedWander($image);
    }

    public function postRemove(Image $image): void
    {
        $this->logger->debug("In SeachIndexer postRemove method");
        $this->updateRelatedWander($image);
    }

    private function updateRelatedWander(Image $image): void
    {
        $this->logger->info("Updating search index for Wander related to Image");
        $wander = $image->getWander();
        if (!$wander instanceof \App\Entity\Wander) {
            return;
        }

        $this->wanderPersister->replaceOne($wander);
    }
}
