<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

define('PHPEXIF_TEST_ROOT', __DIR__);

// Things like e.g. our Markdown Service use the cache; we want a clean cache
// each time we test otherwise we'll end up caching test results from previous
// runs.
(new \Symfony\Component\Filesystem\Filesystem())->remove(__DIR__.'/../var/cache/test');
