<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Neighbourhood;
use App\Repository\NeighbourhoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;

// TODO: We should probably make this a generic interface and have Nominatim
// as just one possible provider. But let's get it working first and see
// what happens.
class LocationService
{
    /** @var NeighbourhoodRepository */
    private $neighbourhoodRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        NeighbourhoodRepository $neighbourhoodRepository)
    {
        $this->logger = $logger;
        $this->neighbourhoodRepository = $neighbourhoodRepository;
    }

    public function setImageLocation(
        Image $image,
        bool $overwriteExisting = false
        ): bool
    {
        if ($overwriteExisting === false && $image->hasLocation()) {
            $this->logger->info('Deliberately not overwriting existing image location');
            return false;
        }

        if (!$image->hasLatlng()) {
            $this->logger->info('Ignoring image that has no latlng.');
            return false;
        }

        $neighbourhood = $this->neighbourhoodRepository
            ->findByLatlng($image->getLatitude(), $image->getLongitude());

        if ($neighbourhood === null) {
            return false;
        }

        $image->setLocation($neighbourhood->getLsoa11ln());
        return true;
    }
}