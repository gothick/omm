<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GeneralExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            // Times
            new TwigFilter('duration_to_hms', [GeneralRuntime::class, 'durationToHMS']),

            // Byte formatting
            new TwigFilter('format_metric_bytes', [GeneralRuntime::class, 'formatMetricBytes']),
            new TwigFilter('format_binary_bytes', [GeneralRuntime::class, 'formatBinaryBytes']),
            // Default byte formatting
            new TwigFilter('format_bytes', [GeneralRuntime::class, 'formatBinaryBytes']),

            // Picture rating
            new TwigFilter('star_rating', [GeneralRuntime::class, 'starRating']),

            // Text
            new TwigFilter('markdown_to_plain_text', [GeneralRuntime::class, 'markdownToPlainText']),
            new TwigFilter('stripmosttags', [GeneralRuntime::class, 'stripMostTags'])
        ];
    }
}