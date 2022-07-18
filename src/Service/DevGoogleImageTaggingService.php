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

        $opts=array(
            // Our staging server doesn't trust itself! TODO: We're not using
            // Vagrant for staging any more. Maybe we don't need this. Plus we
            // should just make the server certificates work.
            "ssl" => array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
            // And on dev, we want to use the Symfony local server proxy
            // (our Mac is set up to see the proxy config on http://127.0.0.1:7080/proxy.pac
            // but we'll just hardcode it here. Hopefully it doesn't change!)
            "http" => [
                'proxy' => 'tcp://localhost:7080'
            ]
        );

        return fopen($url, 'rb', false, stream_context_create($opts));
    }
}
