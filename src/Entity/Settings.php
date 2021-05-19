<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SettingsRepository::class)
 */
class Settings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $siteTitle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $siteSubtitle;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $siteAbout;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $twitterHandle;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteTitle(): ?string
    {
        return $this->siteTitle;
    }

    public function setSiteTitle(?string $siteTitle): self
    {
        $this->siteTitle = $siteTitle;

        return $this;
    }

    public function getSiteSubtitle(): ?string
    {
        return $this->siteSubtitle;
    }

    public function setSiteSubtitle(?string $siteSubtitle): self
    {
        $this->siteSubtitle = $siteSubtitle;

        return $this;
    }

    public function getSiteAbout(): ?string
    {
        return $this->siteAbout;
    }

    public function setSiteAbout(?string $siteAbout): self
    {
        $this->siteAbout = $siteAbout;

        return $this;
    }

    public function getTwitterHandle(): ?string
    {
        return $this->twitterHandle;
    }

    public function setTwitterHandle(?string $twitterHandle): self
    {
        $this->twitterHandle = $twitterHandle;

        return $this;
    }
}
