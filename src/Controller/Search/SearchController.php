<?php

namespace App\Controller\Search;

use App\Entity\Image;
use Elastica\Query;
use Elastica\Query\MatchQuery;
use Elastica\Query\Nested;
use Elastica\Query\QueryString;
use FOS\ElasticaBundle\Finder\FinderInterface;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/search", name="search_")
 *
 */
class SearchController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(PaginatedFinderInterface $imageFinder, PaginatedFinderInterface $wanderFinder, PaginatorInterface $paginator): Response
    {
        // $finder = $this->container->get('fos_elastica.finder.app');

        // TODO: Maybe try combining results from $imageFinder and $wanderFinder?

        $qs = new QueryString('floopy');

        $nested = new Nested();
        $nested->setPath('images');
        $nested->setQuery($qs);
        $results = $wanderFinder->createHybridPaginatorAdapter($nested);



        //dd($wanderFinder);
        //$results = $wanderFinder->createHybridPaginatorAdapter('floopy');
        // $results = $imageFinder->createHybridPaginatorAdapter('floopy');
        $pagination = $paginator->paginate($results);
        dd($pagination);
        // dd($pagination);
        return $this->render('/search/index.html.twig', [
            'pagination' => $pagination
        ]);
    }
}
