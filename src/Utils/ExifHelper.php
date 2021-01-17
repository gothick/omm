<?php

namespace App\Utils;

use PHPExif\Exif;

/**
 * Wrapper for PHPExif's Exif class, which is a little annoying and
 * also has incorrect PHPDoc in a couple of places. Basically translates
 * all calls we use to return exactly the type we need, or null, to
 * make it nice and compatible with our existing code.
 */
class ExifHelper implements ExifHelperInterface
{
    /** @var Exif */
    private $exif;

    public function __construct(Exif $exif)
    {
        $this->exif = $exif;
    }

    public function getTitle():?string
    {
        $title = $this->exif->getTitle();
        if (is_string($title)) {
            return $title;
        }
        return null;
    }
    public function getDescription():?string
    {
        $description = $this->exif->getCaption();
        if (is_string($description)) {
            return $description;
        }
        return null;
    }
    public function getGPS():?array
    {
        $gps = null;
        $gps_as_string = $this->exif->getGPS();
        if (is_string($gps_as_string)) {
            $gps = array_map('doubleval', explode(',', $gps_as_string));
        }
        return $gps;
    }
    public function getKeywords():?array
    {
        $keywords = $this->exif->getKeywords();
        if (is_string($keywords)) {
            // A single keyword comes back as a simple string, not an array. We
            // always return an array.
            $keywords = [ $keywords ];
        }
        if (is_array($keywords)) {
            return $keywords;
        }
        return null;
    }
    public function getCreationDate():?\DateTime
    {
        $creationDate = $this->exif->getCreationDate();
        if ($creationDate instanceof \DateTime) {
            return $creationDate;
        }
        return null;
    }
    public function getRating():?int
    {
        $raw = $this->exif->getRawData();
        if (array_key_exists('XMP-xmp:Rating', $raw)) {
            $rating = $raw['XMP-xmp:Rating'];
            if (is_int($rating)) {
                return $rating;
            }
        }
        return null;
    }

}