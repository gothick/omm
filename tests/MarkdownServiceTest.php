<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\MarkdownService;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class MarkdownServiceTest extends KernelTestCase
{
    /** @var MarkdownService */
    protected $markdownService;

    public static function markdownToTextProvider(): \Iterator
    {
        yield 'null input' => [null, ''];
        yield 'empty string' => ['', ''];
        yield 'simple text' => ['This is a test', "This is a test\n"];
        yield 'formatting' => ['*This* is a _test_', "This is a test\n"];
        yield 'link' => ['*[This](https://gothick.org.uk)* is a _test_', "This is a test\n"];
        yield 'html' => ["I'm <em>just</em> testing...", "I'm just testing...\n"];
        yield 'entities1' => ["I'm just testing & testing", "I'm just testing & testing\n"];
        yield 'entities2' => ["Not Testing < Testing", "Not Testing < Testing\n"];
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $cache = $container->get(TagAwareCacheInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $this->markdownService = new MarkdownService(
            $cache,
            $logger
        );
    }

    #[DataProvider('markdownToTextProvider')]
    public function testMarkdownToText($in, $expected): void
    {
        $this->assertSame(
                $expected,
                $this->markdownService->markdownToText($in)
            );
    }

    public function testFindLinks(): void
    {
        $result = $this->markdownService->findLinks('This [is](https://gothick.org.uk) a test');
        $this->assertSame(
            [
                [
                    'uri' => 'https://gothick.org.uk',
                    'text' => 'is'
                ]
            ], $result, "Couldn't find single link");

        $result = $this->markdownService->findLinks('This [is](https://gothick.org.uk) a test with [two](https://twitter.com/gothick) URLs');
        $this->assertSame(
            [
                [
                    'uri' => 'https://gothick.org.uk',
                    'text' => 'is'
                ],
                [
                    'uri' => 'https://twitter.com/gothick',
                    'text' => 'two'
                ]
            ], $result, "Couldn't find two links"
        );

        $result = $this->markdownService->findLinks(null);
        $this->assertSame(
            [], $result
        );

        $result = $this->markdownService->findLinks('');
        $this->assertSame(
            [], $result
        );

        $result = $this->markdownService->findLinks("I've got <a href='https://gothick.org.uk'>multiple</a> links in [different](https://twitter.com/gothick) formats.");
        $this->assertSame(
            [
                [
                    'uri' => 'https://gothick.org.uk',
                    'text' => 'multiple'
                ],
                [
                    'uri' => 'https://twitter.com/gothick',
                    'text' => 'different'
                ]
            ], $result, "Couldn't find both HTML and Markdown links"
        );

    }
}
