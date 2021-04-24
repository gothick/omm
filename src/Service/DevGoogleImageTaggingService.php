<?php

namespace App\Service;

use App\Entity\Image;

class DevGoogleImageTaggingService extends GoogleImageTaggingService implements ImageTaggingServiceInterface
{
    /**
     * Unlike production, our dev service grabs the actual image data from
     * our local URL and throws it at Google directly, because its URLs
     * aren't public.
     *
     * @return mixed|string
     */
    protected function getImageToSend(Image $image)
    {
        return fopen($image->getMediumImageUri(), 'r');
    }
}