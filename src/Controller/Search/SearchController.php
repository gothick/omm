<?php

namespace App\Controller\Search;

use App\Entity\Image;
use Elastica\Collapse\InnerHits as CollapseInnerHits;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\InnerHits;
use Elastica\Query\MatchQuery;
use Elastica\Query\MultiMatch;
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
            $data = $form->getData();

            // Nested query to find all wanders with images that match
            // the text.
            $nmm = new MultiMatch();
            $nmm->setQuery($data['query']);
            // TODO By the looks of https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
            // you might be able to just not setFields and it'll default to * and might catch everything
            // anyway.
            $nmm->setFields(['images.title', 'images.description', 'images.tags']);

            $nested = new Nested();
            $nested->setPath('images');
            $nested->setQuery($nmm);
            $innerHits = new InnerHits();
            $innerHits->setHighlight(['fields' => [
                'images.title' => [
                    'number_of_fragments' => 0,
                    'no_match_size' => 1024,
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>']
                ],
                'images.description' => [
                    'no_match_size' => 1024,
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>']
                ],
                'images.tags' => [
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>']
                ]
            ]]);
            $nested->setInnerHits($innerHits);

            // Combine that with a normal query to find all wanders that
            // themselves match the text
            $mm = new MultiMatch();
            $mm->setQuery($data['query']);
            $mm->setFields(['title', 'description']);
            $bool = new BoolQuery();
            $bool->addShould($nested);
            $bool->addShould($mm);

            // Wrap it with an outer query to add highlighting to the
            // Wander-level query.
            $searchQuery = new Query();
            $searchQuery->setQuery($bool);
            $searchQuery->setHighlight(['fields' => [
                'title' => [
                    'number_of_fragments' => 0,
                    'no_match_size' => 1024,
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>']
                ],
                'description' => [
                    'no_match_size' => 200,
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>']
                ]
            ]]);

            $results = $wanderFinder->createHybridPaginatorAdapter($searchQuery);
            $pagination = $paginator->paginate(
                $results,
                $request->query->getInt('page', 1),
                10
            );
        }
        return $this->render('/search/index.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination
        ]);
    }
}
