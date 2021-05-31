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

    public function testSetFeaturedImageFromWanderSide()
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

    public function testSetFeaturedImageFromImageSide()
    {
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);

        $wander = $this->entityManager
            ->getRepository(Wander::class)
            ->findOneBy(['title' => 'Test Wander Title for 01-APR-21.GPX']);

        $testImage1->setFeaturingWander($wander);
        $this->assertNotNull($testImage1->getFeaturingWander(), "Image I just set the featuring wander for should have a featuring wander(!)");
        $this->entityManager->flush();
        $this->entityManager->refresh($wander);
        $this->assertNotNull($wander->getFeaturedImage(), "Wander I just set the featured image for should have a featured image");

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

        $this->entityManager->remove($testImage1);
        $this->entityManager->flush();
        $this->entityManager->refresh($wander);
        $this->assertNotNull($wander, "Featuring Wander should remain when featured image is deleted.");
        // Really this is a bit belt-and-braces, as we'd have died on our arse when persisting if we hadn't
        // removed the relationship first.
        $this->assertNull($wander->getFeaturedImage(), "Featuring Wander shouldn't have a featured image if the image was deleted.");
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

    public function testSetAsFeaturedImage()
    {
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);

        $wander = $this->entityManager
            ->getRepository(Wander::class)
            ->findOneBy(['title' => 'Test Wander Title for 01-APR-21.GPX']);

        $testImage1->setWander($wander);
        $this->entityManager->flush();

        $this->entityManager->refresh($testImage1);
        $this->entityManager->refresh($wander);

        $testImage1->setAsFeaturedImage();

        $this->assertNotNull($testImage1->getFeaturingWander(), 'Image should now have a featuring wander.');
        $this->entityManager->flush();
        $this->entityManager->refresh($wander);
        $this->assertNotNull($wander->getFeaturedImage(), "Wander should be updated with featured image.");

    }

    public function testSetAsFeaturedImageWhenNoWanderAssociated()
    {
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);

        $this->expectException(\Exception::class);
        $testImage1->setAsFeaturedImage();
    }

    public function testChangeFeaturedImageFromWanderSide()
    {
        $testImage1 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 1']);

        $wander = $this->entityManager
            ->getRepository(Wander::class)
            ->findOneBy(['title' => 'Test Wander Title for 01-APR-21.GPX']);

        $wander->setFeaturedImage($testImage1);
        $this->entityManager->flush();
        $this->entityManager->refresh($wander);
        $this->entityManager->refresh($testImage1);

        $this->assertSame($wander->getFeaturedImage(), $testImage1, "Wander doesn't have the correct initial featured image associated");
        $this->assertSame($testImage1->getFeaturingWander(), $wander, "Initial Image doesn't have the correct Wander associated");

        $testImage2 = $this->entityManager
            ->getRepository(Image::class)
            ->findOneBy(['title' => 'Test Image 2']);

        $wander->setFeaturedImage($testImage2);
        $this->entityManager->flush();

        $this->entityManager->refresh($wander);
        $this->entityManager->refresh($testImage2);

        $this->assertSame($wander->getFeaturedImage(), $testImage2, "Wander doesn't have the correct new featured image associated");
        $this->assertSame($testImage2->getFeaturingWander(), $wander, "New Image doesn't have the correct Wander associated");
    }
}

