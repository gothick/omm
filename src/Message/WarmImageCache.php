<?php

namespace App\Message;

// Basically https://github.com/liip/LiipImagineBundle/issues/1193#issuecomment-793849806
class WarmImageCache {
    /**
     * WarmImageCache constructor.
     *
     */
    public function __construct(private readonly string $pathToImage, private readonly array $filters = [], private readonly bool $force = false)
    {
    }

    public function isForce(): bool
    {
        return $this->force;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->pathToImage;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}