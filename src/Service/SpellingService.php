<?php

namespace App\Service;

use Mekras\Speller\Source\StringSource;
use Mekras\Speller\Aspell\Aspell;

class SpellingService
{
    // TODO: Dependency injection
    /** @var Aspell */
    private $aspell;

    public function __construct()
    {
        $this->aspell = new Aspell();
    }

    /**
     * Returns simple array of potentially-misspelled words, nothing more for now.
     * @return string[]
     */
    public function checkString(string $input): array
    {
        $misspelledWords = [];
        $results =  $this->aspell->checkText(new StringSource($input), ['en_GB', 'en']);
        // TODO: Something functional
        foreach ($results as $result) {
            $misspelledWords[] = $result->word;
        }

        return $misspelledWords;
    }
}
