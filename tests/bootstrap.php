<?php
namespace App\Tests;

use DG\BypassFinals;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// The Vich uploader stuff has final classes that we need to mock in tests.
// Use BypassFinals to allow that, restricted to the /vich/ path.
BypassFinals::enable();
BypassFinals::allowPaths([
    // Allow bypassing finals in our src/ and tests/ directories
    '*/vich/*'
]);

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

define('PHPEXIF_TEST_ROOT', __DIR__);

// Things like e.g. our Markdown Service use the cache; we want a clean cache
// each time we test otherwise we'll end up caching test results from previous
// runs.
(new \Symfony\Component\Filesystem\Filesystem())->remove(__DIR__.'/../var/cache/test');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
