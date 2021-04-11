<?php

namespace App\Tests;

use App\Service\MarkdownService;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class MarkdownServiceTest extends KernelTestCase
{
    /** @var MarkdownService */
    protected $markdownService;

    protected function setUp(): void
    {
        static::bootKernel();

        $container = self::$container;
        $cache = $container->get(TagAwareCacheInterface::class);
        $parser = $container->get('markdown.parser');
        $logger = $this->createMock(LoggerInterface::class);

        $this->markdownService = new MarkdownService(
            $cache,
            $parser,
            $logger
        );
    }

    /**
     * @dataProvider markdownToTextProvider
     */
    public function testMarkdownToText($in, $expected): void
    {
        $this->assertSame(
                $expected,
                $this->markdownService->markdownToText($in)
            );
    }
    public function markdownToTextProvider(): array
    {
        return [
            'null input'    => [null, ''],
            'empty string'  => ['', ''],
            'simple text'   => ['This is a test', "This is a test\n"],
            'formatting'    => ['*This* is a _test_', "This is a test\n"],
            'link'    => ['*[This](https://gothick.org.uk)* is a _test_', "This is a test\n"],
        ];
    }
    public function testFindLinks(): void
    {
        $result = $this->markdownService->findLinks('This [is](https://gothick.org.uk) a test');
        $this->assertSame(['https://gothick.org.uk'], $result, "Couldn't find single link");

        $result = $this->markdownService->findLinks('This [is](https://gothick.org.uk) a test with [two](https://twitter.com/gothick) URLs');
        $this->assertSame(
            ['https://gothick.org.uk',
            'https://twitter.com/gothick'], $result, "Couldn't find two links"
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
            ['https://gothick.org.uk',
            'https://twitter.com/gothick'], $result, "Couldn't find both HTML and Markdown links"
        );

    }
}
