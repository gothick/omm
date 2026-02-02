<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\Neighbourhood;
use App\Repository\NeighbourhoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use PhpParser\Node\Expr\Cast\Double;
use Psr\Log\LoggerInterface;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;

class NeighbourhoodService implements NeighbourhoodServiceInterface
{
    /** @var NeighbourhoodRepository */
    private $neighbourhoodRepository;

    public function __construct(
        NeighbourhoodRepository $neighbourhoodRepository)
    {
        $this->neighbourhoodRepository = $neighbourhoodRepository;
    }

    public function getNeighbourhood(?float $lat, ?float $lng):?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }
        $neighbourhood = $this
            ->neighbourhoodRepository
            ->findByLatlng($lat, $lng);

        if ($neighbourhood === null) {
            return null;
        } else {
            return $neighbourhood->getLsoa11ln();
        }
    }
}
