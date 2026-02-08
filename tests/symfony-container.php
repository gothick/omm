<?php
// tests/symfony-container.php
// Included by ../rector.php to provide the Symfony container to Rector for use in Rector rules that need it.

use App\Kernel;

require __DIR__ . '/bootstrap.php';

$appKernel = new Kernel('test', false);
$appKernel->boot();

return $appKernel->getContainer();
