<?php

namespace App\DataFixtures;

use App\Entity\Wander;
use App\Service\GpxService;
use App\Service\UploadHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

class ThreeWanderFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private readonly UploadHelper $uploadHelper, private readonly GpxService $gpxService)
    {
    }

    public static function getGroups(): array
    {
        return ['wander'];
    }

    public function load(ObjectManager $manager): void
    {
        $fs = new Filesystem();
        $finder = new Finder();
        foreach($finder->in(__DIR__ . '/gpx/three')->name('/\.gpx$/i') as $source) {
            $targetPath = sys_get_temp_dir() . '/' . $source->getFilename();
            $fs->copy($source->getPathname(), $targetPath);
            $uploadedFile = $this->uploadHelper->uploadGpxFile(new File($targetPath));
            $wander = new Wander();
            $wander->setGpxFilename($uploadedFile);
            $this->gpxService->updateWanderFromGpx($wander);
            $wander->setTitle('Test Wander Title for ' . $source->getFilename());
            $wander->setDescription('Test wander description for ' . $source->getFilename());
            $manager->persist($wander);
        }
        $manager->flush();
    }
}
