<?php

namespace App\Utils;

interface ExifHelperInterface
{
    public function getTitle():?string;
    public function getDescription():?string;
    public function getGPS():?array;
    public function getKeywords():?array;
    public function getCreationDate():?\DateTime;
    public function getRating():?int;
}
