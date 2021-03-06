<?php

namespace App\Twig;

use App\Entity\Wander;
use Twig\Extension\RuntimeExtensionInterface;

class WanderRuntime implements RuntimeExtensionInterface
{
    /** @var string */
    private $imgUrl;

    /** @var string */
    private $fileType;

    public function __construct(string $sectorImgUrl, string $sectorImgUrlFileType)
    {
        $this->imgUrl = $sectorImgUrl; // e.g. '/images/sectors/'
        $this->fileType = $sectorImgUrlFileType;
    }

    public function sectorImgUrl(Wander $wander): string
    {
        // TODO: Maybe default to an unknown image?
        return $this->imgUrl . $wander->getSector() . '.' . $this->fileType;
    }
}