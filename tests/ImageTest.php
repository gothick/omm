<?php

namespace App\Tests;

use App\Entity\Image;
use App\Entity\Tag;
use App\Repository\ImageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Beelab\TagBundle\Tag\TagInterface;

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
    public function testGetTags(): void {
        $image = new Image();
        /** @var iterable<TagInterface> */ $tags = $image->getTags();
        $this->assertIsIterable($tags, "GetTags() should return an iterable even if no tags are present.");
        $this->assertEmpty($tags, "GetTags() should return an empty iterable if no tags are present.");

        $clint = new Tag(); /* The tag with no name */
        $image->addTag($clint);
        $tags = $image->getTags();
        $this->assertIsIterable($tags, "GetTags() should return an iterable.");
        $this->assertCount(1, $tags, "GetTags() should find one tags when we've added one tag.");
        /** @var TagInterface */
        $retrievedTag = $tags->current();
        $this->assertNotNull($retrievedTag, "Retrieved tag should not be null.");
        $this->assertNull($retrievedTag->getName(), "Tag with no name should have a null name.");

        $floopy = new Tag();
        $floopy->setName("floopy");
        $image = new Image();
        $image->addTag($floopy);
        $tags = $image->getTags();
        /** @var TagInterface */
        $hopefullyFloopy = $tags->current();
        $this->assertEquals("floopy", $hopefullyFloopy->getName(), "Unexpected tag name retrieved.");

        $tagAlpha = new Tag();
        $tagAlpha->setName("alpha");
        $tagBeta = new Tag();
        $tagBeta->setName("beta");
        $image = new Image();
        $image->addTag($tagAlpha);
        $image->addTag($tagBeta);
        $this->assertCount(2, $image->getTags(), "Expected two tags after setting two tags.");
        $retrievedTags = $image->getTags();
        $this->assertCount(1, $retrievedTags->filter(fn($tag) => $tag->getName() == 'alpha'), "Couldn't find alpha tag");
        $this->assertCount(1, $retrievedTags->filter(fn($tag) => $tag->getName() == 'beta'), "Couldn't find beta tag");
    }
}
