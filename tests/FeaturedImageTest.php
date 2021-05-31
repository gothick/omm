<?php

namespace App\Tests;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use App\Entity\Wander;

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
            'App\DataFixtures\ThreeWanderFixtures',
            'App\DataFixtures\TwoImageFixtures'
        ]);
    }

    public function testHasFeaturedImage()
    {
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);

        $wander = $this->entityManager
            ->getRepository(Wander::class)
            ->findOneBy(['title' => 'Test Wander Title for 01-APR-21.GPX']);

        $this->assertFalse($wander->hasFeaturedImage(), 'Fresh test wander should not have a featured image.');
        $wander->setFeaturedImage($testImage1);
        $this->assertTrue($wander->hasFeaturedImage(), "Wander should know that we've just set the featured image.");
    }

    public function testSetFeaturedImage()
    {
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);

        $wander = $this->entityManager
            ->getRepository(Wander::class)
            ->findOneBy(['title' => 'Test Wander Title for 01-APR-21.GPX']);

        $wander->setFeaturedImage($testImage1);

        $this->assertNotNull($wander->getFeaturedImage(), "Wander I just set the featured image for should have a featured image(!)");
        $this->entityManager->flush();
        $this->entityManager->refresh($testImage1);
        $this->assertNotNull($testImage1->getFeaturingWander(), "Related Image not updated");
    }

    public function testDeleteFeaturedImage()
    {
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);

        $wander = $this->entityManager
            ->getRepository(Wander::class)
            ->findOneBy(['title' => 'Test Wander Title for 01-APR-21.GPX']);

        $wander->setFeaturedImage($testImage1);
        $this->entityManager->flush();
        $this->entityManager->refresh($testImage1);
        $this->assertNotNull($testImage1->getFeaturingWander(), "Related Image not updated");

        // Really we're just testing that ImageDeleteListener successfully removes the
        // featuring of this image in the featuring Wander, so referential integrity
        // isn't violated when the Image row is deleted.
        $this->entityManager->remove($testImage1);
        $this->entityManager->flush();
        $this->assertNull($wander->getFeaturedImage());

        $this->entityManager->refresh($wander);
        $this->assertNotNull($wander, "Featuring Wander should remain when featured image is deleted.");

    }

    public function testDeleteFeaturingWander()
    {
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);

        $wander = $this->entityManager
            ->getRepository(Wander::class)
            ->findOneBy(['title' => 'Test Wander Title for 01-APR-21.GPX']);

        $wander->setFeaturedImage($testImage1);
        $this->entityManager->flush();

        $this->entityManager->refresh($testImage1);
        $this->entityManager->refresh($wander);

        $this->entityManager->remove($wander);
        $this->entityManager->flush();

        /*
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);
        */
        $this->entityManager->refresh($testImage1);
        $this->assertNotNull($testImage1, "Featured Image should remain even if featuring Wander is deleted.");
        $this->assertNull($testImage1->getFeaturingWander(), "Deleted featuring Wander reference should be cleared from featured Image.");
    }
}

