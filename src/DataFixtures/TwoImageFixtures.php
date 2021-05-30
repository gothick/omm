<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use App\Entity\Image;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGenerator;


class TwoImageFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['image'];
    }

    public function load(ObjectManager $manager): void
    {
        $fs = new Filesystem();
        $finder = new Finder();
        foreach($finder->in(__DIR__ . '/image/two')->name('/\.jpg$/i') as $source) {

            $image = new Image();

            $targetPath = sys_get_temp_dir() . '/' . $source->getFilename();
            $fs->copy($source->getPathname(), $targetPath);
            $uploadedFake = new UploadedFile($targetPath, $source->getFilename(), 'image/jpeg', null, true);
            $image->setImageFile($uploadedFake);
            $manager->persist($image);
        }
        $manager->flush();
    }
}
