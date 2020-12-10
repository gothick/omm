<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GeneralExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('duration_to_hms', [GeneralRuntime::class, 'durationToHMS']),
            new TwigFilter('format_metric_bytes', [GeneralRuntime::class, 'formatMetricBytes']),
            new TwigFilter('format_binary_bytes', [GeneralRuntime::class, 'formatBinaryBytes']),
            // Default
            new TwigFilter('format_bytes', [GeneralRuntime::class, 'formatBinaryBytes'])
        ];
    }
}