<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use App\Entity\Image;
use App\Entity\Wander;
use App\Service\GpxService;
use App\Service\UploadHelper;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WanderWithImages extends Fixture implements FixtureGroupInterface
{
    /** @var UploadHelper */
    private $uploadHelper;

    /** @var GpxService */
    private $gpxService;

    public function __construct(UploadHelper $uploadHelper, GpxService $gpxService)
    {
        $this->uploadHelper = $uploadHelper;
        $this->gpxService = $gpxService;
    }

    public static function getGroups(): array
    {
        return ['image', 'wander', 'wanderwithimages'];
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadWander($manager);
        $this->loadImages($manager);
    }

    public function loadWander(ObjectManager $manager): void
    {
        $fs = new Filesystem();
        $source = __DIR__ . '/gpx/withimages/02-DEC-2022 1336.GPX';
        $targetPath =  sys_get_temp_dir() . '/02-DEC-2022 1336.GPX';
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

    public function loadImages(ObjectManager $manager): void
    {
        $fs = new Filesystem();
        $finder = new Finder();
        foreach ($finder->in(__DIR__ . '/image/forwanderwithimages')->name('/\.jpg$/i') as $source) {

            $image = new Image();

            $targetPath = sys_get_temp_dir() . '/' . $source->getFilename();
            $fs->copy($source->getPathname(), $targetPath);
            $uploadedFake = new UploadedFile($targetPath, $source->getFilename(), 'image/jpeg', null, true);
            $image->setImageFile($uploadedFake);
            $manager->persist($image);
            // We have to flush each time otherwise the Beelab Tag Bundle gets confused and starts duplicating tags. Sigh.
            $manager->flush();
        }
    }
}
