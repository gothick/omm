<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class WanderExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('sectorimgurl', [WanderRuntime::class, 'sectorImgUrl'])
        ];
    }
}