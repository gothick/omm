<?php

namespace App\Service;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use JeroenDesloovere\Geolocation;
use Psr\Log\LoggerInterface;

class DummyLocationTaggingService implements LocationTaggingServiceInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly LoggerInterface $logger)
    {
    }

    /**
     * Tags an image with a known dummy location
     * @param Image $image The image to tag
     * @param bool $overwriteExisting Whether to overwrite existing location tags
     * return bool True if tags were applied, false if not
     */
    public function tagImage(Image $image, bool $overwriteExisting = false): bool
    {
        if ($overwriteExisting === false && $image->hasStreet()) {
            return false;
        }

        if ($image->hasLatlng() === false) {
            // It's not an error, we just ignore it.
            return false;
        }

        try {
            $image->setStreet('Dummy Street at ' . $image->getLatitude() . ', ' . $image->getLongitude());
            $this->entityManager->persist($image);
            $this->entityManager->flush(); // Calling the API's a lot more overhead; we might as well flush on every image.
            return true;
        }
        catch (\Throwable $throwable) {
            $this->logger->error(static::class . ': Error retrieving address for image ID ' . $image->getId() . ': ' . $throwable->getMessage());
            return false;
        }
        return false;
    }
}
