<?php

namespace App\Twig;

use App\Service\MarkdownService;
use App\Service\TagSluggerService;
use ByteUnits\Binary;
use ByteUnits\Metric;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class GeneralRuntime implements RuntimeExtensionInterface
{
    /** @var MarkdownService */
    private $markdownService;

    /** @var TagSluggerService */
    private $slugger;

    public function __construct(MarkdownService $markdownService, TagSluggerService $slugger)
    {
        $this->markdownService = $markdownService;
        $this->slugger = $slugger;
    }

    public function durationToHMS(?DateInterval $interval): string
    {
        if (!isset($interval))
            return "";

        return $interval->format('%hh %im %ss');
    }

    public function starRating(?int $rating): string
    {
        if (is_int($rating) && $rating >= 0) {
            return str_repeat('★', $rating);
        }
        return "-";
    }

    public function formatMetricBytes(int $bytes, string $format = null): string
    {
        return Metric::bytes($bytes)->format($format);
    }
    public function formatBinaryBytes(int $bytes, string $format = null): string
    {
        return Binary::bytes($bytes)->format($format);
    }
    public function markdownToPlainText(?string $markdown): string
    {
        return $this->markdownService->markdownToText($markdown);
    }

    public function stripMostTags(?string $in): string
    {
        if ($in === null) {
            return '';
        }
        return strip_tags($in, '<sup><hr>');
    }

    /**
     * For making our tags nice and safe, so that they can be used
     * as route parameters to our Tag controller.
     */
    public function slugifyTag(?string $in): string
    {
        return $this->slugger->slug($in);
    }
}
