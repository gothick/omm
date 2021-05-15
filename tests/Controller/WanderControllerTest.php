<?php

namespace App\Tests\Controller;

use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class WanderControllerTest Extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    private $databaseTool;

    /** @var KernelBrowser */
    private $client = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();

        // Fixture contains three wanders:
        //   01-APR-21.GPX
        //   01-FEB-21.GPX
        //   01-DEC-20.GPX
        //
        // $wander->setTitle('Test Wander Title for ' . $source->getFilename());
        // $wander->setDescription('Test wander description for ' . $source->getFilename());

        $this->databaseTool->loadFixtures([
            'App\DataFixtures\ThreeWanderFixtures'
        ]);
        //$userRepository = static::$container->get(UserRepository::class);
        //$this->adminUser = $userRepository->findOneByUsername('admin');
    }

    public function testAnythingWorksAtAll(): void
    {
        // :)
        $crawler = $this->client->request('GET', '/wanders');
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('One Mile Matt Wanders');
    }

    public function testIndexPageCanSeeWanders(): void
    {
        // :)
        /** @var Crawler */
        $crawler = $this->client->request('GET', '/wanders');
        $this->assertResponseIsSuccessful();
        // Should be in date order, most recent first
        $this->assertSelectorTextContains('table tbody tr:nth-child(1) td:nth-child(1)', 'Test Wander Title for 01-APR-21.GPX');
        $this->assertSelectorTextContains('table tbody tr:nth-child(2) td:nth-child(1)', 'Test Wander Title for 01-FEB-21.GPX');
        $this->assertSelectorTextContains('table tbody tr:nth-child(3) td:nth-child(1)', 'Test Wander Title for 01-DEC-20.GPX');
        // Shouldn't be *more* than three!
        $this->assertSelectorNotExists('table tbody tr:nth-child(4)');

    }
}
