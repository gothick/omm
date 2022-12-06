<?php

namespace App\Tests;

use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Extra\Markdown\MichelfMarkdown;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Test\IntegrationTestCase;

class TwigMarkdownExtraFilterTest extends IntegrationTestCase
{
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/twig_fixtures_markdown_extra';
    }

    /**
     * Unit tested extensions
     */
    protected function getExtensions(): array
    {
        return [new MarkdownExtension()];
    }

    protected function getRuntimeLoaders(): array
    {
        return [new FactoryRuntimeLoader([
            MarkdownRuntime::class => function (): MarkdownRuntime {
                return new MarkdownRuntime(new MichelfMarkdown());
            }
        ])];
    }
}
