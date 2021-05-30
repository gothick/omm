<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;

class FeaturedImageTest extends KernelTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var AbstractDatabaseTool */
    private $databaseTool;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([
            'App\DataFixtures\ThreeWanderFixtures'
        ]);
    }

    public function testSomething()
    {

    }

}

