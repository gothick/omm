<?php

namespace App\Service;

use App\Repository\SettingsRepository;
use App\Entity\Settings;

class SettingsService
{
    /** @var Settings */
    private $settings;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settings = $settingsRepository->getTheSingleRow();
    }
    public function getSettings()
    {
        return $this->settings;
    }

    // Helpers for Twig
    public function getSiteTitle() : string
    {
        return $this->settings->getSiteTitle() ?? "";
    }

    public function getSiteSubtitle() : string
    {
        return $this->settings->getSiteSubtitle() ?? "";
    }

    public function getSiteAbout() : string
    {
        return $this->settings->getSiteAbout() ?? "";
    }
}