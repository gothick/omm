<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class UserControllerTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    /** @var User */
    private $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $useHttps = getenv('SECURE_SCHEME') === 'https';
        $this->client = self::createClient([], ['HTTPS' => $useHttps]);
        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures([
            \App\DataFixtures\UserFixtures::class
        ]);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $this->adminUser = $userRepository->findOneByUsername('admin');
    }

    public function testChangePasswordShouldRejectEmptyPassword(): void
    {
        $this->client->loginUser($this->adminUser);
        $originalHash = $this->adminUser->getPassword();
        $crawler = $this->client->request(Request::METHOD_GET, '/user/changepassword');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'user_change_password[plainPassword][first]' => '',
            'user_change_password[plainPassword][second]' => '',
        ]);
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $userRepository = self::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneByUsername('admin');
        $this->assertSame($originalHash, $adminUser->getPassword());
    }
}
