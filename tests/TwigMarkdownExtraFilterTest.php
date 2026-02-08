<?php
// TODO: This file is almost identical to TwigGeneralExtensionTest.php. I think one was a test that we started to create but
// never finished. We should probably either finish it or delete it to avoid confusion.
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
    #[\Override]
    protected function getExtensions(): array
    {
        return [new GeneralExtension()];
    }

    #[\Override]
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
