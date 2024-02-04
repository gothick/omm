<?php

use App\Kernel;
use App\CacheKernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
if ('prod' === $kernel->getEnvironment() ||
    'staging' === $kernel->getEnvironment())
{
    $kernel = new CacheKernel($kernel);
    // https://symfony.com/doc/current/reference/configuration/framework.html#http-method-override
    Request::enableHttpMethodParameterOverride();
}
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
// This was suggested when I upgraded framework-bundle. Maybe I'll need it later?
// return function (array $context) {
//     return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
// };
