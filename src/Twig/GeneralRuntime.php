<?php

namespace App\Twig;

use DateTime;
use Twig\Extension\RuntimeExtensionInterface;

class GeneralRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
    }

    public function secondsToHms(?float $seconds): string
    {
        if (!isset($seconds))
            return "";

        $dtF = new DateTime("@0");
        $dtT = new DateTime("@" . $seconds);
        $interval = $dtF->diff($dtT);

        return $interval->format('%H:%I:%S');
    }
}