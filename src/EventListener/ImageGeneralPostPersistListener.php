<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Message\RecogniseImage;
use App\Message\WarmImageCache;
use App\Message\GeolocateImage;
use Symfony\Component\Messenger\MessageBusInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ImageGeneralPostPersistListener {

    /** @var MessageBusInterface */
    private $messageBus;

    /** @var UploaderHelper */
    private $uploaderHelper;

    public function __construct(MessageBusInterface $messageBus, UploaderHelper $uploaderHelper)
    {
        $this->messageBus = $messageBus;
        $this->uploaderHelper = $uploaderHelper;
    }

    public function postPersist(Image $image): void
    {
        // Queue up some image recognition and location tagging
        $id = $image->getId();
        if ($id !== null) {
            $this->messageBus->dispatch(new RecogniseImage($id));
            $this->messageBus->dispatch(new GeolocateImage($id));
        }

        // And warm up the image cache the new image
        $imagePath = $this->uploaderHelper->asset($image);
        if ($imagePath !== null) {
            $this->messageBus->dispatch(new WarmImageCache($imagePath));
        }
    }
}
