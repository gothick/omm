<?php
declare(strict_types=1);
namespace App\Tests;

use DG\BypassFinals;
use PHPUnit\Runner\BeforeTestHook;

dd("Hook code loading");

final class BypassFinalHook implements BeforeTestHook
{
    public function executeBeforeTest(string $test): void
    {
        dd("BypassFinalHook executed before test: $test");
        BypassFinals::enable();
    }
}

