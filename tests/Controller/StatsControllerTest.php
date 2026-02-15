<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\Client;


final class StatsControllerTest extends PantherTestCase
{
    /** @var KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $useHttps = getenv('SECURE_SCHEME') === 'https';
        $this->client = self::createClient([], ['HTTPS' => $useHttps]);
        /** @var AbstractDatabaseTool $databaseTool */
        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        // Fixture contains this wander:
        // GPX: 01-APR-21 125735.GPX
        // $wander->setTitle('Single test wander');
        // $wander->setDescription('Single wander description');

        $databaseTool->loadAllFixtures(['threewandersandtwoimages']);
    }

    public function testSanity(): void
    {
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/stats');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.headline.wanders div.name', 'Wanders');
    }

    public function testShouldBeThreeWanders(): void
    {
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/stats');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.details.wanders table tr:first-child th', 'Total Wander Count:');
        $this->assertSelectorTextContains('.details.wanders table tr:first-child td', '3');
        $this->assertSelectorTextContains('.row.stats-row.neighbourhoods .value', '2');
    }
    public function testShouldBeTwoNeighbourhoods(): void
    {
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/stats');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.row.stats-row.neighbourhoods .value', '2');
    }
    public function testChartJSIsRunning(): void
    {
        $client = static::createPantherClient(
            ['browser' => static::FIREFOX],
            [],
            ['capabilities' => ['acceptInsecureCerts' => true]]
        );

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/stats');
        $this->assertCount(1, $crawler->filter('.row.stats-row.neighbourhoods canvas'));

        $hasPixelData = $client->executeScript(
            'const canvas = document.querySelector(".row.stats-row.neighbourhoods canvas");
            const ctx = canvas.getContext("2d");
            const imageData = ctx.getImageData(25, 25, 10, 10); // Sample a 10x10 area in the center of the canvas
            const data = imageData.data;

            for (let i = 0; i < data.length; i++) {
                if (data[i] > 0) return true;
            }
            return false;'
        );
        $this->assertTrue($hasPixelData);
    }
}
