<?php

namespace App\Tests;

use App\Repository\NeighbourhoodRepository;
use App\Service\LocationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LocationServiceTest extends KernelTestCase
{
    /** @var NeighbourhoodRepository */
    private $neighbourhoodRepository;

    /** @var LocationService */
    private $locationService;

    protected function setUp(): void
    {
        static::bootKernel();

        $container = self::$container;
        $this->neighbourhoodRepository = $container->get(NeighbourhoodRepository::class);

        $this->locationService = new LocationService($this->neighbourhoodRepository);
        /*
        static::bootKernel();

        $container = self::$container;
        $this->neighbourhoodRepository = $container->get(NeighbourhoodRepository::class);
        $neighbourhood = $this->neighbourhoodRepository->findAll();
        //$neighbourhood = $this->neighbourhoodRepository->findByLatlng(51.441480601667, -2.612271505);
        dd($neighbourhood);
        */
    }

    public function testGetLocationName()
    {
        $name = $this->locationService->getLocationName(51.441480601667, -2.612271505);
        $this->assertEquals('Ashton', $name, 'Expected to find Rare Butchers in Ashton');

        $name = $this->locationService->getLocationName(0, 0);
        $this->assertNull($name, 'Expected to find Null Island in no neighbourhood');

        $name = $this->locationService->getLocationName(null, null);
        $this->assertNull($name, 'Expected trying to find neighbourhood with null lat/lng would quietly fail');

    }
}