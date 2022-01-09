<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Entity\Wander;
use App\Service\MarkdownService;
use App\Service\TagSluggerService;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class SearchWanderCustomPropertyListener implements EventSubscriberInterface {

    /** @var MarkdownService */
    private $markdownService;

    /** @var TagSluggerService */
    private $slugger;

    public function __construct(MarkdownService $markdownService, TagSluggerService $slugger)
    {
        $this->markdownService = $markdownService;
        $this->slugger = $slugger;
    }

    public function addCustomProperty(PostTransformEvent $event): void
    {
        $document = $event->getDocument();
        $object = $event->getObject();
        if ($object instanceof Image || $object instanceof Wander) {
            $document->set('title', $this->markdownService->markdownToText($object->getTitle()));
            $document->set('description', $this->markdownService->markdownToText($object->getDescription()));
        }

        if ($object instanceof Image) {
            $document->set('slugifiedTags', $this->slugifyTags($object->getTagNames()));
            $document->set('slugifiedTextTags', $this->slugifyTags($object->getTextTags()));
            $document->set('slugifiedAutoTags', $this->slugifyTags($object->getAutoTags()));
        }
    }

    /**
     * @param mixed $tags
     * @return array<string>
     */
    public function slugifyTags($tags): array
    {
        if ($tags === null || empty($tags)) {
            return [];
        }
        return array_map(fn($tag): string => $this->slugger->slug($tag), $tags);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostTransformEvent::class => 'addCustomProperty',
        ];
    }
}
