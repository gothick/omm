<?php

namespace App\Controller\Api;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use App\Service\SettingsService;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * @Route("/api/", name="api_")
 */
class ApiController extends AbstractController
{
    /**
     *
     * API: Wander list. Returns a basic list of wanders.
     *
     * @Route(
     *  "wanders",
     *  name="wanders_index",
     *  methods={"GET"},
     *  format="json",
     *  condition="'application/json' in request.getAcceptableContentTypes()"
     * )
     * @Cache(
     *  public="true",
     *  smaxage="3600",
     *  maxage="3600"
     * )
     */
    public function wanderIndex(
        WanderRepository $wanderRepository,
        RouterInterface $router
    ): Response {
        $wanders = $wanderRepository
            ->standardQueryBuilder()
            ->orderBy('w.startTime', 'asc')
            ->getQuery()
            ->execute();

        // It's nicer for our JavaScript to be handed the Wander URI on a plate, so we add it
        // to the returned JSON.
        $contentUrlCallback = function (
            /** @scrutinizer ignore-unused */ $innerObject,
            $outerObject,
            /** @scrutinizer ignore-unused */ string $attributeName,
            /** @scrutinizer ignore-unused */ string $format = null,
            /** @scrutinizer ignore-unused */ array $context = []
        ) use ($router) {
            return $router->generate(
                'wanders_show',
                ['id' => $outerObject->getId()]
            );
        };

        return $this->json(
            $wanders,
            Response::HTTP_OK,
            [],
            [
                'groups' => 'wander:list',
                AbstractNormalizer::CALLBACKS => [
                    'contentUrl' => $contentUrlCallback,
                ],
            ]
        );
    }

    /**
     * @Route(
     *  "wanders/{id}",
     *  name="wanders_show",
     *  methods={"GET"},
     *  format="json",
     *  condition="'application/json' in request.getAcceptableContentTypes()"
     * )
     * @Cache(
     *  public="true",
     *  smaxage="3600",
     *  maxage="3600"
     * )
     */
    public function wandersShow(
        Wander $wander,
        RouterInterface $router
    ): Response {
        // It's nicer for our JavaScript to be handed the Wander URI on a plate, so we add it
        // to the returned JSON.
        $contentUrlCallback = function (
            /** @scrutinizer ignore-unused */ $innerObject,
            $outerObject,
            /** @scrutinizer ignore-unused */ string $attributeName,
            /** @scrutinizer ignore-unused */ string $format = null,
            /** @scrutinizer ignore-unused */ array $context = []
        ) use ($router) {
            return $router->generate(
                'wanders_show',
                ['id' => $outerObject->getId()]
            );
        };

        return $this->json(
            $wander,
            Response::HTTP_OK,
            [],
            [
                'groups' => 'wander:item',
                AbstractNormalizer::CALLBACKS => [
                    'contentUrl' => $contentUrlCallback,
                ],
            ]
        );
    }

    /**
     *
     * API: Image list. Returns a basic list of images.
     *
     * @Route(
     *  "images",
     *  name="images_index",
     *  methods={"GET"},
     *  format="json",
     *  condition="'application/json' in request.getAcceptableContentTypes()"
     * )
     * @Cache(
     *  public="true",
     *  smaxage="3600",
     *  maxage="43200"
     * )
     */
    public function imagesIndex(
        ImageRepository $imageRepository
    ): Response {
        $results = $imageRepository
            ->standardQueryBuilder()
            ->where('i.latlng IS NOT NULL')
            ->getQuery()
            ->execute();

        return $this->json(
            $results,
            Response::HTTP_OK,
            [],
            [
                'groups' => 'image:list',
            ]
        );
    }
}
