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
        $this->client = static::createClient(array(), array('HTTPS' => true));
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();

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
        $this->assertPageTitleSame('Wanders');
    }

    public function testIndexPageCanSeeWanders(): void
    {
        // :)
        /** @var Crawler */
        $crawler = $this->client->request('GET', '/wanders');
        $this->assertResponseIsSuccessful();
        // Should be in date order, most recent first
        $this->assertSelectorTextContains('div.wander:nth-child(1) h3 a', 'Test Wander Title for 01-APR-21.GPX', 'April wander missing from index page or in wrong order');
        $this->assertSelectorTextContains('div.wander:nth-child(2) h3 a', 'Test Wander Title for 01-FEB-21.GPX', 'February wander missing from index page or in wrong order');
        $this->assertSelectorTextContains('div.wander:nth-child(3) h3 a', 'Test Wander Title for 01-DEC-20.GPX', 'December wander missing from index page or in wrong order');
        // Shouldn't be *more* than three!
        $this->assertSelectorNotExists('table tbody tr:nth-child(4)');

    }
}
