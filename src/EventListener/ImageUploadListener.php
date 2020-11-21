<?php

namespace App\EventListener;

use App\Entity\Image;
use Exception;
use PHPExif\Reader\Reader;
use Psr\Log\LoggerInterface;
use Vich\UploaderBundle\Event\Event;

class ImageUploadListener
{
    /** @var LoggerInterface $logger */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function onVichUploaderPostUpload(Event $event)
    {
        /** @var \App\Entity\Image $object */
        $object = $event->getObject();
        if ($object instanceof Image) {
            /* TODO: This should probably be in a service */
            if ($object->getMimeType() == 'image/jpeg') {
                try {
                    $reader = Reader::factory(Reader::TYPE_NATIVE);
                    $exif = $reader->read($object->getImageFile()->getPathname());

                    $title = $exif->getTitle();
                    $description = $exif->getCaption();
                    $gps = $exif->getGPS();
                    $keywords = $exif->getKeywords();
                    
                    if ($title !== false) {
                        $object->setTitle($title);
                    }
                    
                    if ($description !== false) {
                        $object->setDescription($description);
                    }

                    if ($gps !== false) {
                        $array = array_map('doubleval', explode(',', $gps));
                        $object->setLatlng($array);
                    }
                    if ($keywords !== false) {
                        $keywords = is_array($keywords) ? $keywords : array($keywords);
                        $object->setKeywords($keywords);
                    }
                }
                catch(Exception $e) {
                    // It's not that important if we can't update the data
                    // from the JPEG image. We can always set it manually later.
                    $this->logger->error('Error getting image Exif information: ' . $e->getMessage());
                }
            } else {
                $this->logger->info('Ignoring non-JPEG file when trying to set properties from EXIT.');
            }
            $this->logger->error("Hey, did you start to upload things that aren't images using Vich?");
        }
        // $image->setTitle("Flobadob");
        // $mapping = $event->getMapping();
    }
}