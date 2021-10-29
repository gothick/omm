<?php

namespace App\Tests\Controller\Admin;

use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;

class SettingsControllerTest Extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    private $databaseTool;

    /** @var KernelBrowser */
    private $client = null;

    /** @var User */
    private $adminUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->databaseTool = self::$container->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([
            'App\DataFixtures\UserFixtures'
        ]);
        $userRepository = static::$container->get(UserRepository::class);
        $this->adminUser = $userRepository->findOneByUsername('admin');
    }

    public function testLoggedIn(): void
    {
        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/settings/');
        $this->assertResponseIsSuccessful();
    }

    public function testNotLoggedIn(): void
    {
        $crawler = $this->client->request('GET', '/admin/settings/');
        $this->assertResponseRedirects('http://localhost/login');
    }

    public function testClickEdit(): void
    {
        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/settings/');
        $this->assertResponseIsSuccessful();
        $this->client->clickLink('Edit settings');
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Edit Settings');
    }

    public function testEditSettings(): void
    {
        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/settings/edit');
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->submitForm('settings_save', [
            'settings[siteTitle]' => 'Test Site Title',
            'settings[siteSubtitle]' => 'Test Site Subtitle',
            'settings[twitterHandle]' => 'testTwitterHandle',
            'settings[siteAbout]' => 'Test Site About Text'
        ]);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertRouteSame('admin_settings_index');
        $this->assertSelectorTextContains('tbody tr:nth-child(1) td:nth-child(2)', 'Test Site Title');
        $this->assertSelectorTextContains('tbody tr:nth-child(2) td:nth-child(2)', 'Test Site Subtitle');
        $this->assertSelectorTextContains('tbody tr:nth-child(3) td:nth-child(2)', 'testTwitterHandle');
        $this->assertSelectorTextContains('tbody tr:nth-child(4) td:nth-child(2)', 'Test Site About Text');
    }

}
