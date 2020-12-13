<?php

namespace App\Twig;

use ByteUnits\Binary;
use ByteUnits\Metric;
use DateInterval;
use DateTime;
use Twig\Extension\RuntimeExtensionInterface;

class GeneralRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
    }

    public function durationToHMS(?DateInterval $interval): string
    {
        if (!isset($interval))
            return "";

        return $interval->format('%H:%I:%S');
    }

    public function starRating(?int $rating): string
    {
        if (is_int($rating) && $rating >= 0) {
            return str_repeat('★', $rating);
        }
        return "No rating";
    }

    public function formatMetricBytes(int $bytes, string $format = null): string
    {
        return Metric::bytes($bytes)->format($format);
    }
    public function formatBinaryBytes(int $bytes, string $format = null): string
    {
        return Binary::bytes($bytes)->format($format);
    }
}