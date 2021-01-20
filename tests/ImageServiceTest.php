<?php

namespace App\Tests;

use App\Entity\Image;
use App\Service\ImageService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ImageServiceTest extends KernelTestCase
{
    public function testSetPropertiesFromExif()
    {
        self::bootKernel();
        $container = self::$container;
        /** @var ImageService */
        $imageService = $container->get("App\Service\ImageService");

        $mock = $this->createMock(Image::class);
        $mock->method('getMimeType')
            ->willReturn('image/gif');
        $imageService->setPropertiesFromEXIF($mock, false);
    }
}