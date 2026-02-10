<?php

namespace App\Service;

use App\Repository\NeighbourhoodRepository;

class NeighbourhoodService implements NeighbourhoodServiceInterface
{
    public function __construct(private readonly NeighbourhoodRepository $neighbourhoodRepository)
    {
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
