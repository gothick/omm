<?php

namespace App\Tests;

use App\Service\MarkdownService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class MarkdownServiceTest extends KernelTestCase
{
    /** @var MarkdownService */
    protected $markdownService;

    public static function markdownToTextProvider(): array
    {
        return [
            'null input'    => [null, ''],
            'empty string'  => ['', ''],
            'simple text'   => ['This is a test', "This is a test\n"],
            'formatting'    => ['*This* is a _test_', "This is a test\n"],
            'link'    => ['*[This](https://gothick.org.uk)* is a _test_', "This is a test\n"],
            'html'  => ["I'm <em>just</em> testing...", "I'm just testing...\n"],
            'entities1' => ["I'm just testing & testing", "I'm just testing & testing\n"],
            'entities2' => ["Not Testing < Testing", "Not Testing < Testing\n"]
        ];
    }

    protected function setUp(): void
    {
        static::bootKernel();

        $container = static::getContainer();
        $cache = $container->get(TagAwareCacheInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->markdownService = new MarkdownService(
            $cache,
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
