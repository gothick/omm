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
    public function __construct(private readonly Exif $exif)
    {
    }

    public function getTitle():?string
    {
        $title = $this->exif->getTitle();
        if ($title === false) {
            return null;
        }

        // I cast to string to work around a weird bug in
        // what looks like exiftool, where PHPExif's use
        // of it uses its JSON output, and exiftool's
        // JSON returns titles that look like numbers
        // (e.g. "42") as integer (i.e. unquoted 42 in the
        // JSON.)
        return (string) $title;
    }

    public function getDescription():?string
    {
        $description = $this->exif->getCaption();
        if ($description === false) {
            return null;
        }

        return (string) $description;
    }

    public function getGPS():?array
    {
        $gps = []; // Match the default in our Image entity
        $gps_as_string = $this->exif->getGPS();
        if (is_string($gps_as_string)) {
            $gps = array_map(floatval(...), explode(',', $gps_as_string));
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

        return []; // Match the default in our Image entity
    }

    public function getCreationDate():?\DateTime
    {
        $creationDate = $this->exif->getCreationDate();
        if ($creationDate instanceof \DateTime) {
            // PHPExif assumes that the camera time string is UTC. It's not for many
            // people's cameras, including mine. Re-interpret it as local time in
            // Bristol. Not much of a bodge given that this project is specifically
            // limited to a small geographical region!
            $converted = new \DateTime($creationDate->format("Y-m-d\TH:i:s"), new \DateTimeZone("Europe/London"));
            $converted->setTimezone(new \DateTimeZone("UTC"));
            return $converted;
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

    public function getLocation(): ?string
    {
        $raw = $this->exif->getRawData();
        if (array_key_exists('IPTC:Sub-location', $raw)) {
            $location = $raw['IPTC:Sub-location'];
            if (is_string($location)) {
                return $location;
            }
        }

        return null;
    }
}