<?php

namespace App\Service;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use ErrorException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;

class ImaggaService implements ImageTaggingServiceInterface
{
    /** @var Client */
    private $guzzle;

    public function __construct(
        string $imaggaApiKey,
        string $imaggaApiSecret,
        string $baseUri,
        private readonly EntityManagerInterface $entityManager)
    {
        $stack = HandlerStack::create();
        // TODO: Parameterise rate limit
        $stack->push(RateLimiterMiddleware::perSecond(1));

        $this->guzzle = new Client([
            'handler' => $stack,
            // TODO: Parameterise URI
            'base_uri' => $baseUri,
            'auth' => [
                $imaggaApiKey,
                $imaggaApiSecret
            ]
        ]);
    }

    public function tagImage(Image $image, bool $overwriteExisting = false): bool
    {
        if ($overwriteExisting === false && $image->getAutoTagsCount() > 0) {
            return false;
        }

        $response = $this->guzzle->request('GET', '/v2/tags', [
            'query' => [
                'image_url' => $image->getMediumImageUri(),
                // TODO: Find a better way of faking this in the dev environment
                // Maybe just always tag the images with some test tags?
                // 'image_url' => 'https://omm.gothick.org.uk/media/cache/srcset_576/uploads/images/20210328-mg-9921-terry-house-6067172c952d1544478055.jpg',
                'threshold' => 15.0
            ]
        ]);

        $content = (string) $response->getBody();
        $imagga_result = json_decode($content);

        if ($imagga_result->status->type != 'success') {
            throw new ErrorException("Error returned from imagga: " . $imagga_result->status->text);
        }

        $tags = [];
        foreach($imagga_result->result->tags as $tag) {
            $tags[] = $tag->tag->en;
        }

        $image->setAutoTags($tags);
        $this->entityManager->persist($image);
        $this->entityManager->flush(); // Calling the API's a lot more overhead; we might as well flush on every image.
        return true;
    }
}
