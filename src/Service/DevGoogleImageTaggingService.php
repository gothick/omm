<?php

namespace App\Service;

use App\Entity\Image;
use Exception;

class DevGoogleImageTaggingService extends GoogleImageTaggingService implements ImageTaggingServiceInterface
{
    /**
     * Unlike production, our dev service grabs the actual image data from
     * our local URL and throws it at Google directly, because its URLs
     * aren't public. We don't read off the filesystem because filesystem
     * storage is just one option in Liip Imagine (or even Vich uploader.)
     *
     * @return resource|bool
     */
    protected function getImageToSend(Image $image)
    {
        $url = $image->getMediumImageUri();
        if ($url === null) {
            throw new Exception("Couldn't get image URL in image tagger.");
        }
        // Our staging server doesn't trust itself!
        $opts=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        return fopen($url, 'rb', false, stream_context_create($opts));
    }
}
