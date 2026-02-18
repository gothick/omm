<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\User;

final class ImageControllerTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    /** @var User */
    private $adminUser;

    /** @var int */
    private $firstImageId;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $useHttps = getenv('SECURE_SCHEME') === 'https';
        $this->client = self::createClient([], ['HTTPS' => $useHttps]);
        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures([
            \App\DataFixtures\TwoImageFixtures::class,
            \App\DataFixtures\UserFixtures::class
        ]);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $this->adminUser = $userRepository->findOneByUsername('admin');

        $imageRepository = self::getContainer()->get(\App\Repository\ImageRepository::class);
        $this->firstImageId = $imageRepository->findAll()[0]->getId();
        $this->urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
    }

    public function testClickEdit(): void
    {
        $this->client->loginUser($this->adminUser);
        $url = $this->urlGenerator->generate('admin_image_show', ['id' => $this->firstImageId]);
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
        $this->client->clickLink('edit');
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Edit Image');
    }

    public function testDeleteImage(): void
    {
        $imageRepository = self::getContainer()->get(\App\Repository\ImageRepository::class);
        $this->assertSame(2, $imageRepository->count([]));
        $this->client->loginUser($this->adminUser);
        $url = $this->urlGenerator->generate('admin_image_edit', ['id' => $this->firstImageId]);
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('Delete');
        $this->assertResponseRedirects($this->urlGenerator->generate('admin_image_index'));
        $this->assertSame(1, $imageRepository->count([]));
    }
}
