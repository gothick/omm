<?php

namespace App\Tests;

use App\Entity\Image;
use App\Repository\WanderRepository;
use App\Service\ImageService;
use App\Service\LocationService;
use Exception;
use InvalidArgumentException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
    }

    public function testToolPath()
    {
        // Similar to our setUp function, only we want to create our own
        // ImageService just for this test.
        $uploaderHelper = $this->createMock(UploaderHelper::class);
        $cacheManager = $this->createMock(CacheManager::class);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $wanderRepository = $this->createMock(WanderRepository::class);
        $locationService = $this->createMock(LocationService::class);
        $imagesDirectory = PHPEXIF_TEST_ROOT . '/test_data_images/';

        // The path to an actual copy of exiftool in this test
        // environment. We just want to make sure that passing
        // an explicit path that should work, works:
        $exiftool_path = getenv('TEST_EXIFTOOL_PATH');

        $imageService = new ImageService(
            $uploaderHelper,
            $cacheManager,
            $router,
            $logger,
            $wanderRepository,
            $locationService,
            $imagesDirectory,
            $exiftool_path
        );
        $this->assertInstanceOf(ImageService::class, $imageService, "Couldn't create ImageService with exiftool path that should be valid: " . $exiftool_path);

        $this->expectException(InvalidArgumentException::class);
        $imageService = new ImageService(
            $uploaderHelper,
            $cacheManager,
            $router,
            $logger,
            $wanderRepository,
            $locationService,
            $imagesDirectory,
            '/floople/oojimaflip/wrong'
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

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $this->setName('20210411-ommtest-Title Numeric.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertEquals('42', $image->getTitle(), 'Failed to set title for a  numeric title string');
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

    public function testReadLocationText()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Location With.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $result = $image->getLocation();
        $this->assertEquals('Hotwells & Harbourside', $result, 'Failed to read location from image');

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Location Without.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $this->assertNull($image->getLocation(), 'Unexpected location set from image with no location');
    }

    public function testSetLocationFromLatLng()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20200925-IMG_3823-Location Without But Has LatLng.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $result = $image->getLocation();
        $this->assertEquals('Rome', $result, "setPropertiesFromEXIF should fall back to a GPS-based location lookup.");

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20200925-IMG_3823-Location Without LatLng Is Null Island.jpg');
        $this->imageService->setPropertiesFromEXIF($image, false);
        $result = $image->getLocation();
        $this->assertNull($result, "Null Island should quietly resolve to a null location");
    }

    public function testStandaloneLocationSetter()
    {
        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Location With.jpg');
        $this->imageService->setLocationFromEXIF($image);
        $result = $image->getLocation();
        $this->assertEquals('Hotwells & Harbourside', $result, 'Failed to read location from image using standalone setter');

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Location Without.jpg');
        $this->imageService->setLocationFromEXIF($image);
        $this->assertNull($image->getLocation(), 'Unexpected location set from image with no location using standalone setter');

        $image = new Image();
        $image->setLocation('Existing');
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Location With.jpg');
        $this->imageService->setLocationFromEXIF($image);
        $result = $image->getLocation();
        $this->assertEquals('Existing', $result, 'Standalone Location setter unexpectedly overwrote data.');
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

