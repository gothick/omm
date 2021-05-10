<?php

namespace App\Service;

use App\Entity\Image;
use Exception;

class DevGoogleImageTaggingService extends GoogleImageTaggingService implements ImageTaggingServiceInterface
{
    /**
     * Unlike production, our dev service grabs the actual image data from
     * our local URL and throws it at Google directly, because its URLs
     * aren't public.
     *
     * @return resource|bool
     */
    protected function getImageToSend(Image $image)
    {
        $url = $image->getMediumImageUri();
        if ($url === null) {
            throw new Exception("Couldn't get image URL in image tagger.");
        }
        return fopen($url, 'r');
    }
}
