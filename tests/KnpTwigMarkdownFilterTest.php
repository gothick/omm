<?php

namespace App\Tests;

use Knp\Bundle\MarkdownBundle\Parser\ParserManager;
use Knp\Bundle\MarkdownBundle\Parser\Preset\Max;
use Knp\Bundle\MarkdownBundle\Twig\Extension\MarkdownTwigExtension;
use Twig\Test\IntegrationTestCase;

class KnpTwigMarkdownFilterTest extends IntegrationTestCase
{
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/twig_fixtures_markdown';
    }

    /**
     * Unit tested extensions
     */
    protected function getExtensions(): array
    {
        $pm = new ParserManager();
        $pm->addParser(new Max(), "default");
        return [new MarkdownTwigExtension($pm)];
    }

    /*
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
    */
}
