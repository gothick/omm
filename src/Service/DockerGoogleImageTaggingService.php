<?php

namespace App\Service;

use App\Entity\Image;
use Exception;

class DockerGoogleImageTaggingService extends GoogleImageTaggingService implements ImageTaggingServiceInterface
{
    /**
     * Quick bodge for now. We'll have to try to figure out how to get image tagging
     * working more consistently across all our various environments.
     * @return resource|bool
     */
    protected function getImageToSend(Image $image)
    {
        $url = $image->getMediumImageUri();
        if ($url === null) {
            throw new Exception("Couldn't get image URL in image tagger.");
        }

        return fopen($url, 'rb', false);
    }
}
