<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GeneralExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('seconds_to_hms', [GeneralRuntime::class, 'secondsToHms'])
        ];
    }
}