<?php

namespace App\Controller\Search;

use App\Entity\Image;
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
        // TODO: Maybe try combining results from $imageFinder and $wanderFinder?

        //dd($wanderFinder);
        $results = $wanderFinder->createHybridPaginatorAdapter('ipsum');
        //$results = $imageFinder->createHybridPaginatorAdapter('tell');
        $pagination = $paginator->paginate($results);
        //dd($pagination);
        // dd($pagination);
        return $this->render('/search/index.html.twig', [
            'pagination' => $pagination
        ]);
    }
}
