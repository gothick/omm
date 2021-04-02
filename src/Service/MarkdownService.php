<?php

namespace App\Service;

use Delight\Str\Str;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class MarkdownService
{
    /** @var TagAwareCacheInterface */
    private $cache;

    /** @var MarkdownParserInterface */
    private $markdownParser;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TagAwareCacheInterface $cache,
        MarkdownParserInterface $markdownParserInterface,
        LoggerInterface $logger
        )
    {
        $this->cache = $cache;
        $this->markdownParser = $markdownParserInterface;
        $this->logger = $logger;
    }

    public function markdownToText(?string $markdown): string
    {
        if ($markdown === null || $markdown === '') {
            return '';
        }
        $key = 'md_' . md5($markdown);
        return $this->cache->get($key, function(ItemInterface $item) use ($markdown) {
            $item->tag('markdown_text');
            return strip_tags($this->markdownParser->transformMarkdown($markdown));
        });
    }
}
