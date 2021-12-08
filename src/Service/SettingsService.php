<?php

namespace App\Service;

use App\Repository\SettingsRepository;
use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;

class SettingsService
{
    /** @var Settings */
    private $settings;

    public function __construct(SettingsRepository $settingsRepository, EntityManagerInterface $entityManager)
    {
        $settings = $settingsRepository->getTheSingleRow();
        if ($settings === null) {
            // Minuscule chance of a race condition but in that worst-case scenario
            // we'll always bring back the first row from the database when we
            // getTheSingleRow() so all that will happen is that an extra row
            // will languish in the database forever.
            $settings = new Settings();
            $entityManager->persist($settings);
            $entityManager->flush();
        }
        $this->settings = $settings;
    }

    public function getSettings(): Settings
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

    public function displayTwitterCards(): bool
    {
        return !empty($this->settings->getTwitterHandle());
    }

    public function getTwitterHandle(): string
    {
        return $this->settings->getTwitterHandle() ?? "";
    }

    public function getGravatarEmail(): string
    {
        return $this->settings->getGravatarEmail() ?? "";
    }
}
