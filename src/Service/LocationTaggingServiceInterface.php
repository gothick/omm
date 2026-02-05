<?php

namespace App\Service;

use App\Entity\Image;

interface LocationTaggingServiceInterface
{
    public function tagImage(Image $image, bool $overwriteExisting = false): bool;
}
