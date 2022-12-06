<?php

namespace App\Service;

use DOMDocument;
use Michelf\Markdown;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class MarkdownService
{
    /** @var TagAwareCacheInterface */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TagAwareCacheInterface $cache,
        LoggerInterface $logger
        )
    {
        $this->cache = $cache;
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
            return html_entity_decode(strip_tags(Markdown::defaultTransform($markdown)), ENT_QUOTES);
        });
    }

    public function findLinks(?string $markdown): array
    {
        if ($markdown === null || $markdown === '') {
            return [];
        }

        $results = [];
        $html = Markdown::defaultTransform($markdown);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        //dd($dom->saveHTML());
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $results[] = [
                'uri' => $link->getAttribute('href'),
                'text' => $link->textContent
            ];
        }
        return $results;
    }
}
