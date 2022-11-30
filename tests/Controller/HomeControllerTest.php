<?php

namespace App\Tests\Controller;

use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class HomeControllerTest Extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    private $databaseTool;

    /** @var KernelBrowser */
    private $client = null;

    public function setUp(): void
    {
        parent::setUp();
        $use_https = getenv('SECURE_SCHEME') === 'https';
        $this->client = static::createClient(array(), array('HTTPS' => $use_https));
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();

        // Fixture contains this wander:
        // GPX: 01-APR-21 125735.GPX
        // $wander->setTitle('Single test wander');
        // $wander->setDescription('Single wander description');

        $this->databaseTool->loadFixtures([
            'App\DataFixtures\SingleWanderFixture'
        ]);
        //$userRepository = static::$container->get(UserRepository::class);
        //$this->adminUser = $userRepository->findOneByUsername('admin');
    }

    public function testAnythingWorksAtAll(): void
    {
        // :)
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testShouldHaveOneWander(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.headerstrip .row .col', 'Total Wanders: 1');
    }
}
