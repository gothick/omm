<?php

declare(strict_types=1);

namespace App\Service;

interface NeighbourhoodServiceInterface
{
    public function getNeighbourhood(?float $lat, ?float $lng): ?string;
}
