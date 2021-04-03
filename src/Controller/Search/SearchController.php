<?php

namespace App\Controller\Search;

use App\Entity\Image;
use Elastica\Collapse\InnerHits as CollapseInnerHits;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\InnerHits;
use Elastica\Query\MatchQuery;
use Elastica\Query\Nested;
use Elastica\Query\QueryString;
use FOS\ElasticaBundle\Finder\FinderInterface;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/search", name="search_")
 *
 */
class SearchController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET", "POST"})
     */
    public function index(
        Request $request,
        //PaginatedFinderInterface $imageFinder,
        PaginatedFinderInterface $wanderFinder,
        PaginatorInterface $paginator): Response
    {
        // $finder = $this->container->get('fos_elastica.finder.app');

        // TODO: Maybe try combining results from $imageFinder and $wanderFinder?

        $form = $this->createFormBuilder(null, ['method' => 'GET' ])
            ->add('query', SearchType::class, ['label' => false])
            ->getForm();

        $form->handleRequest($request);
        $pagination = null;
        if ($form->isSubmitted() && $form->isValid()) {
            //$nested->setInnerHits(new InnerHits());

            $data = $form->getData();
            $qs = new QueryString($data['query']);

            // This finds Wanders with images that match the query
            $nested = new Nested();
            $nested->setPath('images');
            $nested->setQuery($qs);
            $innerHits = new InnerHits();
            $innerHits->setHighlight(['fields' => [
                'images.title' => new \stdClass(),
                'images.description' => new \stdClass()
            ]]);
            $nested->setInnerHits($innerHits);
            $bool = new BoolQuery();
            $bool->addShould($nested);
            $bool->addShould($qs);

            $searchQuery = new Query();
            $searchQuery->setQuery($bool);
            $searchQuery->setHighlight(['fields' => [
                'title' => new \stdClass(),
                'description' => new \stdClass()
            ]]);

            $results = $wanderFinder->createHybridPaginatorAdapter($searchQuery);
            $pagination = $paginator->paginate($results);
            dd($pagination);
        }
        return $this->render('/search/index.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination
        ]);
    }
}
