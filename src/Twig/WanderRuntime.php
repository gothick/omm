<?php

namespace App\Twig;

use App\Entity\Wander;
use Twig\Extension\RuntimeExtensionInterface;

class WanderRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly string $sectorImgUrl, private readonly string $sectorImgUrlFileType)
    {
    }

    public function sectorImgUrl(Wander $wander): string
    {
        // TODO: Maybe default to an unknown image?
        return $this->sectorImgUrl . $wander->getSector() . '.' . $this->sectorImgUrlFileType;
    }
}
