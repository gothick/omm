<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Bridge\Symfony\Routing\SymfonyRoutesProvider;
use Rector\Symfony\Contract\Bridge\Symfony\Routing\SymfonyRoutesProviderInterface;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;
use Rector\Renaming\Rector\Class_\RenameAttributeRector;


// https://github.com/rectorphp/rector-symfony?tab=readme-ov-file

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/docker',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
        // SymfonySetList::SYMFONY_64,
        // SymfonySetList::SYMFONY_70,
        // SymfonySetList::SYMFONY_71,
        // SymfonySetList::SYMFONY_72,
        // SymfonySetList::SYMFONY_73,
        // SymfonySetList::SYMFONY_74,
    ])
    ->withAttributesSets(
        symfony: true,
        sensiolabs: true,
        doctrine: true
    )
    // ->withPreparedSets(symfonyConfigs: true)
    // ->withPreparedSets(deadCode: true)
    // ->withPreparedSets(symfonyCodeQuality: true)
    // ->withPreparedSets(doctrineCodeQuality: true)
    // ->withPreparedSets(codeQuality: true)
    // ->withPreparedSets(codingStyle: true)
    // ->withPreparedSets(carbon: true)
    // ->withPreparedSets(phpunitCodeQuality: true)
    // ->withSkip([
    //     RenameAttributeRector::class
    // ])
    ->withPhpVersion(PhpVersion::PHP_83);
