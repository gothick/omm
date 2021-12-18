<?php

namespace App\Controller;

use Elastica\Query;
use Elastica\Query\InnerHits;
use Elastica\Query\MultiMatch;
use Elastica\Query\Nested;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{
    /** @var array<string, array<string, array<int, string>|string>> $translateParam */
    private static $translateParam = [
        'any'       => [
            'fields' => ['images.tags', 'images.autoTags', 'images.textTags'],
            'description' => 'all tag types'
        ],
        'hand-tag'  => [
            'fields' => ['images.tags'],
            'description' => 'only hand-created tags'
        ],
        'auto-tag'  => [
            'fields' => ['images.autoTags'],
            'description' => 'only tags created by automatic image recognition'
        ],
        'text-tag'  => [
            'fields' => ['images.textTags'],
            'description' => 'only tags created by automatic text recognition'
        ],
    ];

    /**
     * @Route("/tag/{tag}/{type}", name="tag", methods={"GET"}, requirements={"type": "any|hand-tag|auto-tag|text-tag"})
     */
    public function index(
        string $tag,
        Request $request,
        PaginatedFinderInterface $wanderFinder,
        PaginatorInterface $paginator,
        string $type = "any"
    ): Response {
        $fields = self::$translateParam[$type]['fields'];
        $searchDescription = self::$translateParam[$type]['description'];

        $nmm = new MultiMatch();
        $nmm->setQuery($tag);
        // TODO By the looks of https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
        // you might be able to just not setFields and it'll default to * and might catch everything
        // anyway.
        $nmm->setFields($fields);

        $nested = new Nested();
        $nested->setPath('images');
        $nested->setQuery($nmm);

        $innerHits = new InnerHits();
        // We want more than the default three inner hits, as there may be several related
        // images on a particular wander, but we don't want to bump things up too high otherwise
        // an overly-broad search will bring back way too many images even with pagination of
        // the outer results.
        $innerHits->setSize(10);
        $nested->setInnerHits($innerHits);

        $results = $wanderFinder->createHybridPaginatorAdapter($nested);
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10 // TODO: Parameterise this results-per-page
        );

        return $this->render('tag/index.html.twig', [
            'tag' => $tag,
            // TODO: Take this back out; we'll only need to pass the pagination in the long run. I'm debugging.
            'results' => $results,
            'pagination' => $pagination,
            'search_description' => $searchDescription
        ]);
    }
}
