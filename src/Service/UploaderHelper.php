<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploaderHelper
{

    /** @var SluggerInterface */
    private $slugger;

    /** @var string */
    private $gpxDirectory;

    public function __construct(
        SluggerInterface $slugger,
        string $gpxDirectory
    )
    {
        $this->slugger = $slugger;
        $this->gpxDirectory = $gpxDirectory;
    }

    public function uploadGpxFile(File $gpxFile): string
    {
        if ($gpxFile instanceof UploadedFile) {
            $originalFilename = pathinfo($gpxFile->getClientOriginalName(), PATHINFO_FILENAME);
        } else {
            $originalFilename = $gpxFile->getFilename();
        }
        $safeFilename = $this->slugger->slug(/** @scrutinizer ignore-type */ $originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $gpxFile->guessExtension();
        try {
            $gpxFile->move(
                $this->gpxDirectory,
                $newFilename
            );
        }
        catch (FileException $e) {
            throw new HttpException(500, "Failed finishing GPX upload: " . $e->getMessage());
        }
        return $newFilename;
    }
}