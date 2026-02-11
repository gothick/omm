<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class HomeControllerTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $useHttps = getenv('SECURE_SCHEME') === 'https';
        $this->client = self::createClient([], ['HTTPS' => $useHttps]);
        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        // Fixture contains this wander:
        // GPX: 01-APR-21 125735.GPX
        // $wander->setTitle('Single test wander');
        // $wander->setDescription('Single wander description');

        $databaseTool->loadFixtures([
            \App\DataFixtures\SingleWanderFixture::class
        ]);
        //$userRepository = static::$container->get(UserRepository::class);
        //$this->adminUser = $userRepository->findOneByUsername('admin');
    }

    public function testAnythingWorksAtAll(): void
    {
        // :)
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/');
        $this->assertResponseIsSuccessful();
    }

    public function testShouldHaveOneWander(): void
    {
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.headerstrip .row .col', 'Total Wanders: 1');
    }
}
