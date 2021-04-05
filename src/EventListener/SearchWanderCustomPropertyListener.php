<?php

namespace App\EventListener;

use App\Service\MarkdownService;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchWanderCustomPropertyListener implements EventSubscriberInterface {

    /** @var MarkdownService */
    private $markdownService;

    public function __construct(MarkdownService $markdownService)
    {
        $this->markdownService = $markdownService;
    }

    public function addCustomProperty(PostTransformEvent $event)
    {
        $document = $event->getDocument();
        $object = $event->getObject();
        $document->set('title', $this->markdownService->markdownToText($object->getTitle()));
        $document->set('description', $this->markdownService->markdownToText($object->getDescription()));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostTransformEvent::class => 'addCustomProperty',
        ];
    }

}