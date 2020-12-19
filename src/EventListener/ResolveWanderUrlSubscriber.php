<?php
// Based on https://api-platform.com/docs/core/file-upload/
// api/src/EventSubscriber/ResolveMediaObjectContentUrlSubscriber.php

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use App\Entity\MediaObject;
use App\Entity\Wander;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

final class ResolveWanderUrlSubscriber implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface */
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onPreSerialize', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function onPreSerialize(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response || !$request->attributes->getBoolean('_api_respond', true)) {
            return;
        }

        if (!($attributes = RequestAttributesExtractor::extractAttributes($request)) || !\is_a($attributes['resource_class'], Wander::class, true)) {
            return;
        }
        $wanders = $controllerResult;

        if (!is_iterable($wanders)) {
            $wanders = [$wanders];
        }

        foreach ($wanders as $wander) {

            if (!$wander instanceof Wander) {
                continue;
            }
            $wander->contentUrl = $this->router->generate(
                    'wanders_show',
                    ['id' => $wander->getId()]
            );
        }
    }
}