<?php

namespace App\Service;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use JeroenDesloovere\Geolocation;
use Psr\Log\LoggerInterface;

class GoogleLocationTaggingService implements LocationTaggingServiceInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Geolocation\Geolocation */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        string $googleApiKey,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    )
    {
        $this->client = new Geolocation\Geolocation(
            $googleApiKey,
            true /* use SSL */
        );
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Tags an image with location data from Google Maps API
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
            $address = $this->client->getAddress($image->getLatitude(), $image->getLongitude());
            $result = $address->getResult();

            // Google Maps "route" is the street name, it seems.
            $routeLongName = null;
            foreach (($result->address_components ?? []) as $component) {
                if (in_array('route', $component->types ?? [], true)) {
                    $routeLongName = $component->long_name ?? null;
                    break;
                }
            }
            if ($routeLongName !== null) {
                $image->setStreet($routeLongName);
                $this->entityManager->persist($image);
                $this->entityManager->flush(); // Calling the API's a lot more overhead; we might as well flush on every image.
                return true;
            }
            return false;
        }
        catch (Geolocation\Exception $e) {
            // You can't always expect to get an address.
            $this->logger->warning('GoogleLocationTaggingService: No address retrieved for image ID ' . $image->getId() . ': ' . $e->getMessage());
            return false;
        }
        catch (\Throwable $th) {
            $this->logger->error('GoogleLocationTaggingService: Error retrieving address for image ID ' . $image->getId() . ': ' . $th->getMessage());
            return false;
        }
        return false;
    }
}
