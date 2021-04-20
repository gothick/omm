<?php

namespace App\Message;

// Basically https://github.com/liip/LiipImagineBundle/issues/1193#issuecomment-793849806
class WarmImageCache {
    /** @var string */
    private $pathToImage;
    /** @var array */
    private $filters;
    /** @var bool  */
    private $force = false;
    /**
     * WarmImageCache constructor.
     *
     */
    public function __construct(string $pathToImage, array $filters = [], bool $force = false)
    {
        $this->pathToImage = $pathToImage;
        $this->filters = $filters;
        $this->force = $force;
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

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}