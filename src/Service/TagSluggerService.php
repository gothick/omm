<?php

namespace App\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

class TagSluggerService
{
    /** @var SluggerInterface */
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }
    /**
     * Our tags can contain almost anything, but we want to use them in URL parameters.
     * Instead we slugify them consistently for feeding to Elastica as special slugifed
     * versions, and also use this filter to generate the URL parameters, thus making
     * everything nice and safe.
     *
     * @param string|null $in
     */
    public function slug($in): string
    {
        if ($in === null || $in === "") {
            return '-';
        }
        $slugged = $this->slugger->slug($in);
        return $slugged == "" ? "-" : $slugged;
    }
}
