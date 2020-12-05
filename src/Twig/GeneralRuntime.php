<?php

namespace App\Twig;

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
}