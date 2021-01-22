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

}

