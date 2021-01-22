<?php

namespace App\Tests;

use App\Entity\Image;
use App\Repository\WanderRepository;
use App\Service\ImageService;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ImageServiceTest extends TestCase
{
    /** @var ImageService */
    protected $imageService;

    protected function setUp(): void
    {
        $uploaderHelper = $this->createMock(UploaderHelper::class);
        $cacheManager = $this->createMock(CacheManager::class);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $wanderRepository = $this->createMock(WanderRepository::class);
        $imagesDirectory = PHPEXIF_TEST_ROOT . '/test_data_images/';

        $this->imageService = new ImageService(
            $uploaderHelper,
            $cacheManager,
            $router,
            $logger,
            $wanderRepository,
            $imagesDirectory,
            null //?string $exiftoolPath
        );

    }

    public function testCapturedAt()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-CapturedAt With.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $expected = new \DateTime("@1549892566");
        $this->assertEquals($expected, $image->getCapturedAt(), "Unexpected value for capturedAt when reading image");
    }

    public function testReadRating()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Stars 5.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertEquals(5, $image->getRating(), "Failed to read five-star rating from image.");

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Stars None.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertEquals(0, $image->getRating(), "Failed to read zero rating from image");
    }

    public function testReadTitle()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Title With.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertEquals("Title With", $image->getTitle(), "Failed to read title from image.");

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Title Without.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertEquals(null, $image->getTitle(), "Failed to set empty title from image");
    }

    public function testReadDescription()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Caption With.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertEquals("This is a caption", $image->getDescription(), "Failed to read description from image.");

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Caption Without.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertEquals(null, $image->getDescription(), "Failed to set empty description from image");
    }

    public function testReadCoords()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Location With.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $result = $image->getLatlng();
        //dd($result);
        $this->assertIsArray($result, "Latitude/Longitude pair should be in array");
        $this->assertCount(2, $result, "Latitude/Longitude should be two numbers");
        $this->assertEqualsWithDelta(51.448236285, $result[0], "0.000001", "Latitude seems adrift");
        $this->assertEqualsWithDelta(-2.6241279266667, $result[1], "0.000001", "Longitude seems adrift");

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Location Without.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertIsArray($image->getLatlng(), "An image with no co-ordinates should leave the lat/lon as an array");
        $this->assertCount(0, $image->getLatlng(), "An image with no co-ordinates should result in an empty lat/lon array");
    }

    public function testKeywords()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Keywords None.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $keywords = $image->getKeywords();
        $this->assertIsArray($keywords, "Reading image with no keywords should still produce an array, albeit empty");
        $this->assertEmpty($keywords, "Reading image with no keywords should result in an empty array");

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Keywords One.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $keywords = $image->getKeywords();
        $this->assertIsArray($keywords, "Reading an image with a single keyword should still result in an array");
        $this->assertCount(1, $keywords, "Reading image with a single keyword should give an array with one element");

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Keywords Multiple.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $keywords = $image->getKeywords();
        $this->assertIsArray($keywords, "Reading an image with multiple keywords should result in an array");
        $this->assertCount(7, $keywords, "Reading multiple keyword test image should find six keywords");
        $this->assertContains("stack of books", $keywords, "Multi-word keyword not found in multiple keyword test image");
        $this->assertContains("Bristol", $keywords, "Expected word not found in mutliple keyword test image");
        $this->assertContains("Places", $keywords, "Expected word not found in mutliple keyword test image");
        $this->assertContains("UK", $keywords, "Expected word not found in mutliple keyword test image");
        $this->assertContains("books", $keywords, "Expected word not found in mutliple keyword test image");
        $this->assertContains("desk", $keywords, "Expected word not found in mutliple keyword test image");
        $this->assertContains("united kingdom", $keywords, "Expected word not found in mutliple keyword test image");
    }

    public function testNonJPEG()
    {
        $image = new Image();
        $image->setMimeType('image/gif');
        $image->setName('20190211-ommtest-Location With.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $result = $image->getLatlng();
        $this->assertIsArray($result, "Trying to read a non-JPEG file should keep the default value");
        $this->assertCount(0, $result, "Trying to read a non-JPEG file shouldn't set any properties, even if they exist on the test image");
    }

    // TODO: Test capturedAt

    public function testMinimalMetadata()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Minimal Metadata.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);

        $this->assertIsArray($image->getKeywords());
        $this->assertCount(0, $image->getKeywords());

        $this->assertIsArray($image->getLatlng());
        $this->assertCount(0, $image->getLatlng());

        $this->assertNull($image->getTitle());
        $this->assertNull($image->getDescription());
        $this->assertNull($image->getCapturedAt());
    }
}

