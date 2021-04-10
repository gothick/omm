<?php

namespace App\Service;

use App\Entity\Image;

interface ImaggaServiceInterface
{
    public function tagImage(Image $image, bool $overwriteExisting = false): bool;
}