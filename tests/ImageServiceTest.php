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
    public function testSetPropertiesFromExif()
    {
        $uploaderHelper = $this->createMock(UploaderHelper::class);
        $cacheManager = $this->createMock(CacheManager::class);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $wanderRepository = $this->createMock(WanderRepository::class);
        $imagesDirectory = PHPEXIF_TEST_ROOT . '/test_data_images/';

        $imageService = new ImageService(
            $uploaderHelper,
            $cacheManager,
            $router,
            $logger,
            $wanderRepository,
            $imagesDirectory,
            null //?string $exiftoolPath
        );

        $image = new Image();
        $image->setMimeType('image/jpeg');
        $image->setName('20190211-ommtest-Stars 5.jpg');
        $imageService->setPropertiesFromEXIF($image, false);
        // dd($image);
        $this->assertEquals(5, $image->getRating());
    }
}