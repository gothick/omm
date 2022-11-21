<?php

namespace App\Tests;

use App\Entity\Image;
use App\Entity\Tag;
use App\Repository\ImageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    protected function setUp(): void
    {
        /*
        $uploaderHelper = $this->createMock(UploaderHelper::class);
        $cacheManager = $this->createMock(CacheManager::class);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $wanderRepository = $this->createMock(WanderRepository::class);

        $locationService = $this->createMock(LocationService::class);
        $locationService->method('getLocationName')
            ->will($this->returnCallback( function($lat, $lng) {
                // This fakes what the location service does well enough
                if ($lat === null || $lng === null) {
                    return null;
                }
                // Null Island
                if ($lat === 0.0 && $lng === 0.0) {
                    return null;
                }
                return 'Rome'; // All roads lead to Rome
            }));

        $imagesDirectory = PHPEXIF_TEST_ROOT . '/test_data_images/';

        $this->imageService = new ImageService(
            $uploaderHelper,
            $cacheManager,
            $router,
            $logger,
            $wanderRepository,
            $locationService,
            $imagesDirectory,
            null //?string $exiftoolPath
        );
        */
    }

    /**
     * @group tags
     */
    public function testGetTagNames(): void {
        $image = new Image();
        $tags = $image->getTagNames();
        $this->assertIsIterable($tags, "Reading image with no keywords should still produce an array, albeit empty");
        $this->assertEmpty($tags, "Reading image with no keywords should result in an empty array");
    }
}
