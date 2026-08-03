<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Image;

interface ImageTaggingServiceInterface
{
    public function tagImage(Image $image, bool $overwriteExisting = false): bool;
}
