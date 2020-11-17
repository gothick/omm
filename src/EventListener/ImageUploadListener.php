<?php

namespace App\EventListener;

use Vich\UploaderBundle\Event\Event;

class ImageUploadListener
{
    public function onVichUploaderPostUpload(Event $event)
    {
        /* @var $object App\Entity\Image */
        $object = $event->getObject();
        // $image->setTitle("Flobadob");
        // $mapping = $event->getMapping();
    }
}