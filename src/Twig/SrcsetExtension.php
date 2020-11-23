<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SrcsetExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('srcset', [SrcsetRuntime::class, 'srcset'])
        ];
    }
}