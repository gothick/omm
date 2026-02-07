<?php

namespace App\MessageHandler;

use App\Message\WarmImageCache;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class WarmImageCacheHandler {
    /** @var FilterManager  */
    private $filterManager;
    /** @var FilterService */
    private $filterService;

    public function __construct(
        FilterManager $filterManager,
        FilterService $filterService)
    {
        $this->filterManager = $filterManager;
        $this->filterService = $filterService;
    }

    /**
     * @param WarmImageCache $message
    */
    public function __invoke(WarmImageCache $message): void
    {
        $path = $message->getPath();
        $filters = $message->getFilters() ?: array_keys($this->filterManager->getFilterConfiguration()->all());
        foreach ($filters as $filter)
        {
            if ($message->isForce()) {
                $this->filterService->bustCache($path, $filter);
            }

            $results[$filter] = $this->filterService->getUrlOfFilteredImage($path, $filter);
        }
    }
}
