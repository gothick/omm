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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

// NB: All /api routes are configured to be stateless in the security.yml
// firewall config. You could also make them stateless using an annotation:
// https://symfony.com/doc/current/routing.html#stateless-routes
#[Route(path: '/api/', name: 'api_')]
class ApiController extends AbstractController
{
    private readonly bool $shouldSetCacheFields;

    public function __construct(string $kernelEnvironment)
    {
        // TODO: I used to control the caching with annotations (https://symfony.com/bundles/SensioFrameworkExtraBundle/current/annotations/cache.html)
        // but I couldn't find any way of making them environment sensitive, and I didn't want
        // caching in the dev environment. Is there a better way? api-platform did it automatically;
        // maybe you could have a look in their code...
        $this->shouldSetCacheFields = $kernelEnvironment === 'dev' ? false : true;
    }

    private function addCacheHeaders(
        Response $r,
        int $maxAge = 3600,
        int $sharedMaxAge = 3600
    ): Response
    {
        if ($this->shouldSetCacheFields) {
            $r->setMaxAge($maxAge)
                ->setSharedMaxAge($sharedMaxAge)
                ->setPublic();
        }
        return $r;
    }

    /**
     *
     * API: Wander list. Returns a basic list of wanders.
     */
    #[Route(path: 'wanders', name: 'wanders_index', methods: ['GET'], format: 'json', condition: "'application/json' in request.getAcceptableContentTypes()")]
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
        $contentUrlCallback = (fn(
            /** @scrutinizer ignore-unused */
            $innerObject,
            $outerObject,
            /** @scrutinizer ignore-unused */
            string $attributeName,
            /** @scrutinizer ignore-unused */
            string $format = null,
            /** @scrutinizer ignore-unused */
            array $context = []
        ) => $router->generate(
            'wanders_show',
            ['id' => $outerObject->getId()]
        ));

        $response = $this->json(
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
        return $this->addCacheHeaders($response);
    }

    #[Route(path: 'wanders/{id}', name: 'wanders_show', methods: ['GET'], format: 'json', condition: "'application/json' in request.getAcceptableContentTypes()")]
    public function wandersShow(
        Wander $wander,
        RouterInterface $router
    ): Response {
        // It's nicer for our JavaScript to be handed the Wander URI on a plate, so we add it
        // to the returned JSON.
        $contentUrlCallback = (fn(
            /** @scrutinizer ignore-unused */
            $innerObject,
            $outerObject,
            /** @scrutinizer ignore-unused */
            string $attributeName,
            /** @scrutinizer ignore-unused */
            string $format = null,
            /** @scrutinizer ignore-unused */
            array $context = []
        ) => $router->generate(
            'wanders_show',
            ['id' => $outerObject->getId()]
        ));

        $response = $this->json(
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
        return $this->addCacheHeaders($response);
    }

    /**
     *
     * API: Image list. Returns a basic list of images.
     */
    #[Route(path: 'images', name: 'images_index', methods: ['GET'], format: 'json', condition: "'application/json' in request.getAcceptableContentTypes()")]
    public function imagesIndex(
        ImageRepository $imageRepository
    ): Response {
        $results = $imageRepository
            ->standardQueryBuilder()
            ->where('i.latlng IS NOT NULL')
            ->getQuery()
            ->execute();

        $response = $this->json(
            $results,
            Response::HTTP_OK,
            [],
            [
                'groups' => 'image:list',
            ]
        );
        return $this->addCacheHeaders($response, 43201, 4600);
    }
}
