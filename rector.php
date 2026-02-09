<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Bridge\Symfony\Routing\SymfonyRoutesProvider;
use Rector\Symfony\Contract\Bridge\Symfony\Routing\SymfonyRoutesProviderInterface;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;


// https://github.com/rectorphp/rector-symfony?tab=readme-ov-file

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/docker',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/templates'
    ])
    ->withFileExtensions(['php', 'twig'])
    ->withComposerBased(doctrine: true)
    ->withComposerBased(symfony: true)
    // ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(
        php83: true
    )
    ->withSets([
        // SymfonySetList::SYMFONY_74,
    ])
    ->withAttributesSets(
        symfony: true,
        sensiolabs: true,
        doctrine: true,
        phpunit: true
    )
    ->withPreparedSets(symfonyConfigs: true)
    ->withPreparedSets(deadCode: true)
    ->withPreparedSets(symfonyCodeQuality: true)
    ->withPreparedSets(doctrineCodeQuality: true)
    ->withComposerBased(doctrine: true)
    ->withPreparedSets(codeQuality: true)
    ->withPreparedSets(codingStyle: true)
    ->withPreparedSets(carbon: true)
    ->withPreparedSets(phpunitCodeQuality: true);
