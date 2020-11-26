<?php

namespace App\Twig;

use App\Entity\Image;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Twig\Extension\RuntimeExtensionInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class SrcsetRuntime implements RuntimeExtensionInterface
{
    /** @var UploaderHelper */
    private $uploaderHelper;
    /** @var CacheManager */
    private $imagineCacheManager;

    private $filters;

    public function __construct(
        UploaderHelper $uploaderHelper, 
        FilterManager $filterManager, 
        CacheManager $imagineCacheManager)
    {
        $this->uploaderHelper = $uploaderHelper;
        $this->imagineCacheManager = $imagineCacheManager;

        $this->filters = [];
        foreach($filterManager->getFilterConfiguration()->all() as $name => $filter) {
            if (preg_match('/^srcset/', $name)) {
                $width = $filter['filters']['relative_resize']['widen'];
                $this->filters[] = [
                    'filter' => $name,
                    'width' => $width
                ];
            }
        }
    }

    public function srcset(Image $image)
    {
        $image_asset_path = $this->uploaderHelper->asset($image);
        $srcset = [];
        foreach($this->filters as $filter) {
            $srcset[] = [
                'width' => $filter['width'],
                'path' => $this->imagineCacheManager->getBrowserPath($image_asset_path, $filter['filter'])
            ];
        }
        
        // Add original image as srcset option, otherwise it may never be used.
        $srcset[] = ['width' => $image->getDimensions()[0], 'path' => $image_asset_path];

        $srcsetString = implode(', ', array_map(function ($src) {
            return sprintf(
                '%s %uw',
                $src['path'],
                $src['width']
            );
        }, $srcset));

        return $srcsetString;
    }
}