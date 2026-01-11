<?php

namespace App\Tests;

use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\MarkdownRuntime;
use App\Twig\GeneralExtension;
use App\Twig\GeneralRuntime;
use Twig\Extra\Markdown\MichelfMarkdown;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Test\IntegrationTestCase;

use App\Service\MarkdownService;
use App\Service\TagSluggerService;

class TwigMarkdownExtraFilterTest extends IntegrationTestCase
{
    #[\Override]
    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/twig_fixtures';
    }

    /**
     * Unit tested extensions
     */
    protected function getExtensions(): array
    {
        return [new MarkdownExtension(), new GeneralExtension()];
    }

    protected function getRuntimeLoaders(): array
    {
        return [new FactoryRuntimeLoader([
            MarkdownRuntime::class => function (): MarkdownRuntime {
                return new MarkdownRuntime(new MichelfMarkdown());
            },
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
