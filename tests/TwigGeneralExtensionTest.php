<?php

namespace App\Tests;

use App\Service\MarkdownService;
use App\Service\TagSluggerService;
use App\Twig\GeneralExtension;
use App\Twig\GeneralRuntime;
use PHPUnit\Framework\TestCase;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Test\IntegrationTestCase;

class TwigGeneralExtensionTest extends IntegrationTestCase
{
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/twig_fixtures';
    }

    /**
     * Unit tested extensions
     */
    protected function getExtensions(): array
    {
        return [new GeneralExtension()];
    }

    protected function getRuntimeLoaders(): array
    {
        return [new FactoryRuntimeLoader([
            GeneralRuntime::class => function (): GeneralRuntime {
                // Later we'll actually need to create a MarkdownService, or at least mock it.
                // For now we're just testing a non-Markdown filter or two
                $markdown = $this->createMock(MarkdownService::class);

                $tagslugger = $this->createMock(TagSluggerService::class);
                return new GeneralRuntime($markdown, $tagslugger);
            }
        ])];
    }
}
