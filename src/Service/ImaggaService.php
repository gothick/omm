<?php

namespace App\Service;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use ErrorException;
use GuzzleHttp\Client;

class ImaggaService
{
    /** @var Client */
    private $guzzle;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        string $imaggaApiKey,
        string $imaggaApiSecret,
        EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->guzzle = new Client([
            'base_uri' => 'https://api.imagga.com',
            'auth' => [
                $imaggaApiKey,
                $imaggaApiSecret
            ]
        ]);
    }

    public function tagImage(Image $image): void
    {
        $response = $this->guzzle->request('GET', '/v2/tags', [
            'query' => [
                //'image_url' => $image->getMediumImageUri(),
                'image_url' => 'https://omm.gothick.org.uk/media/cache/srcset_576/uploads/images/20210328-mg-9921-terry-house-6067172c952d1544478055.jpg',
                'threshold' => 15.0
            ]
        ]);

        $content = (string) $response->getBody();
        $imagga_result = json_decode($content);

        if ($imagga_result->status->type != 'success') {
            //$output->writeln('<error>Error returned from imagga:</error>');
            //$output->writeln('<error> Type: ' . $imagga_result->status->type . '</error>');
            //$output->writeln('<error> Text: ' . $imagga_result->status->text . '</error>');
            throw new ErrorException("Error returned from imagga: " . $imagga_result->status->text);
        }

        $tags = [];
        foreach($imagga_result->result->tags as $tag) {
            $tags[] = $tag->tag->en;
        }
        $image->setAutoTags($tags);
        $this->entityManager->persist($image);
    }
}
