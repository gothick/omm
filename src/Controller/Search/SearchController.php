<?php

namespace App\Controller\Search;

use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\InnerHits;
use Elastica\Query\MultiMatch;
use Elastica\Query\Nested;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/search', name: 'search_')]
class SearchController extends AbstractController
{
    public function __construct(private readonly \FOS\ElasticaBundle\Finder\PaginatedFinderInterface $wanderFinder, private readonly \Knp\Component\Pager\PaginatorInterface $paginator)
    {
    }

    #[Route(path: '/', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request): Response
    {
        $queryString = "";

        $form = $this->createFormBuilder(null, [
            'method' => 'GET',
            'csrf_protection' => false //
            ])
            ->add('query', SearchType::class, ['label' => false])
            ->getForm();

        $form->handleRequest($request);

        $pagination = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $rawQuery = is_array($data) ? ($data['query'] ?? '') : '';
            $queryString = is_scalar($rawQuery) ? trim((string) $rawQuery) : '';

            if ($queryString !== '') {
                // Boost results where all terms are present across combined text fields,
                // but keep any-term matching as a fallback for broader discovery.
                $imageAllTerms = new MultiMatch();
                $imageAllTerms->setQuery($queryString);
                $imageAllTerms->setFields([
                    'images.title',
                    'images.description',
                    'images.neighbourhood',
                    'images.street'
                ]);
                $imageAllTerms->setType('cross_fields');
                $imageAllTerms->setOperator('and');
                $imageAllTerms->setParam('boost', 4.0);

                $imageAnyTerms = new MultiMatch();
                $imageAnyTerms->setQuery($queryString);
                $imageAnyTerms->setFields([
                    'images.title',
                    'images.description',
                    'images.tags',
                    'images.autoTags',
                    'images.textTags',
                    'images.neighbourhood',
                    'images.street'
                ]);

                $imageBool = new BoolQuery();
                $imageBool->addShould($imageAllTerms);
                $imageBool->addShould($imageAnyTerms);
                $imageBool->setMinimumShouldMatch(1);

                $nested = new Nested();
                $nested->setPath('images');
                $nested->setQuery($imageBool);

                $innerHits = new InnerHits();
                // We want more than the default three inner hits, as there may be several related
                // images on a particular wander, but we don't want to bump things up too high otherwise
                // an overly-broad search will bring back way too many images even with pagination of
                // the outer results.
                $innerHits->setSize(10);
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
                    'images.street' => [
                        'no_match_size' => 1024,
                        'pre_tags' => ['<mark>'],
                        'post_tags' => ['</mark>']
                    ],
                    'images.neighbourhood' => [
                        'no_match_size' => 1024,
                        'pre_tags' => ['<mark>'],
                        'post_tags' => ['</mark>']
                    ]
                    // We don't need to highlight tag hits, as they're Elasticsearch keywords and will
                    // only be found by an exact match on the search term. We highlight them more simply
                    // just by looking at which ones match $queryString in the twig template.
                ]]);
                $nested->setInnerHits($innerHits);

                $wanderAllTerms = new MultiMatch();
                $wanderAllTerms->setQuery($queryString);
                $wanderAllTerms->setFields(['title', 'description']);
                $wanderAllTerms->setType('cross_fields');
                $wanderAllTerms->setOperator('and');
                $wanderAllTerms->setParam('boost', 4.0);

                $wanderAnyTerms = new MultiMatch();
                $wanderAnyTerms->setQuery($queryString);
                $wanderAnyTerms->setFields(['title', 'description']);

                $wanderBool = new BoolQuery();
                $wanderBool->addShould($wanderAllTerms);
                $wanderBool->addShould($wanderAnyTerms);
                $wanderBool->setMinimumShouldMatch(1);

                $bool = new BoolQuery();
                $bool->addShould($nested);
                $bool->addShould($wanderBool);
                $bool->setMinimumShouldMatch(1);

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

                $results = $this->wanderFinder->createHybridPaginatorAdapter($searchQuery);
                $pagination = $this->paginator->paginate(
                    $results,
                    $request->query->getInt('page', 1),
                    10
                );
            }
        }

        return $this->render('search/index.html.twig', [
            'query_string' => $queryString,
            'form' => $form,
            'pagination' => $pagination
        ]);
    }
}
