<?php

declare(strict_types=1);

namespace App\Tests;

use App\Repository\NeighbourhoodRepository;
use App\Service\NeighbourhoodService;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


final class LocationServiceTest extends KernelTestCase
{
    /** @var NeighbourhoodService */
    private $neighbourhoodService;

    /** @var AbstractDatabaseTool */
    private $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $container = self::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();

        $neighbourhoodRepository = $container->get(NeighbourhoodRepository::class);
        $this->neighbourhoodService = new NeighbourhoodService($neighbourhoodRepository);
    }

    public function testGetLocationName()
    {
        $this->databaseTool->loadFixtures([
            \App\DataFixtures\NeighbourhoodFixtures::class,
        ]);

        $name = $this->neighbourhoodService->getNeighbourhood(51.441480601667, -2.612271505);
        $this->assertEquals('Ashton', $name, 'Expected to find Rare Butchers in Ashton');

        $name = $this->neighbourhoodService->getNeighbourhood(0, 0);
        $this->assertNull($name, 'Expected to find Null Island in no neighbourhood');

        $name = $this->neighbourhoodService->getNeighbourhood(null, null);
        $this->assertNull($name, 'Expected trying to find neighbourhood with null lat/lng would quietly fail');

    }
}
