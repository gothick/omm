<?php

namespace App\Service;

use App\Repository\NeighbourhoodRepository;

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

        return $neighbourhood?->getLsoa11ln();
    }
}
