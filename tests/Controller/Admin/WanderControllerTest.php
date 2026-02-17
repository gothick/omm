<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class WanderControllerTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    /** @var User */
    private $adminUser;

    /** @var int */
    private $latestWanderId;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $useHttps = getenv('SECURE_SCHEME') === 'https';
        $this->client = self::createClient([], ['HTTPS' => $useHttps]);
        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures([
            \App\DataFixtures\ThreeWanderFixtures::class,
            \App\DataFixtures\UserFixtures::class
        ]);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $this->adminUser = $userRepository->findOneByUsername('admin');

        $wanderRepository = self::getContainer()->get(\App\Repository\WanderRepository::class);
        $this->latestWanderId = $wanderRepository->createQueryBuilder('w')
            ->select('w.id')
            ->orderBy('w.startTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
        $this->urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
    }

    public function testClickEdit(): void
    {
        $this->client->loginUser($this->adminUser);
        $url = $this->urlGenerator->generate('admin_wanders_show', ['id' => $this->latestWanderId]);
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
        $this->client->clickLink('edit');
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Edit Wander');
    }

    public function testDeleteWander(): void
    {
        $wanderRepository = self::getContainer()->get(\App\Repository\WanderRepository::class);
        $this->assertSame(3, $wanderRepository->count([]));
        $this->client->loginUser($this->adminUser);
        $url = $this->urlGenerator->generate('admin_wanders_edit', ['id' => $this->latestWanderId]);
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('Delete');
        $this->assertResponseRedirects($this->urlGenerator->generate('admin_wanders_index'));
        $this->assertSame(2, $wanderRepository->count([]));
    }
}
