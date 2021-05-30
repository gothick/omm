<?php

namespace App\EventListener;

use App\Entity\Wander;
use App\Entity\Image;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ImageDeleteListener
{
    public function preRemove(
            Image $image,
            LifecycleEventArgs $event
        ): void
    {
        // If we're about to delete an image, we want to remove it as a featured
        // image from the featuring Wander first, otherwise we'll break referential
        // integrity.
        $wander = $image->getFeaturingWander();
        if ($wander) {
            $wander->setFeaturedImage(null);
        }
    }
}