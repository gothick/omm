<?php

namespace App\Message;

class RecogniseImage {
    /** @var int */
    private $imageId;

    /** @var bool */
    private $overwrite;

    public function __construct(int $imageId, bool $overwrite = false) {
        $this->imageId = $imageId;
        $this->overwrite = $overwrite;
    }
    public function getImageId(): int {
        return $this->imageId;
    }
    public function getOverwrite(): bool {
        return $this->overwrite;
    }
}