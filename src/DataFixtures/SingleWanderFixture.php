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
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

class SingleWanderFixture extends Fixture implements FixtureGroupInterface
{
    public function __construct(private readonly UploadHelper $uploadHelper, private readonly GpxService $gpxService)
    {
    }

    public static function getGroups(): array
    {
        return ['single_wander'];
    }

    public function load(ObjectManager $manager): void
    {
        $fs = new Filesystem();
        $source = __DIR__ . '/gpx/01-APR-21 125735.GPX';
        $targetPath =  sys_get_temp_dir() . '/01-APR-21 125735.GPX';
        $fs->copy($source, $targetPath);
        $uploadedFile = $this->uploadHelper->uploadGpxFile(new File($targetPath));
        $wander = new Wander();
        $wander->setGpxFilename($uploadedFile);

        $this->gpxService->updateWanderFromGpx($wander);
        $wander->setTitle('Single test wander');
        $wander->setDescription('Single wander description');

        $manager->persist($wander);
        $manager->flush();
    }
}
