<?php

namespace App\Message;

class RecogniseImage {
    public function __construct(private readonly int $imageId, private readonly bool $overwrite = false)
    {
    }

    public function getImageId(): int {
        return $this->imageId;
    }

    public function getOverwrite(): bool {
        return $this->overwrite;
    }
}