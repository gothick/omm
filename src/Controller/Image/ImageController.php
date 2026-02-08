<?php

namespace App\Controller\Image;

use App\Entity\Image;
use App\Form\ImageFilterData;
use App\Form\ImageFilterType;
use App\Repository\ImageRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/image', name: 'image_')]
class ImageController extends AbstractController
{
    #[Route(path: '/{id}', name: 'show', methods: ['GET'])]
    public function show(Image $image, ImageRepository $imageRepository): Response
    {
        $prev = $imageRepository->findPrev($image);
        $next = $imageRepository->findNext($image);
        return $this->render('image/show.html.twig', [
            'image' => $image,
            'prev' => $prev,
            'next' => $next
        ]);
    }

    #[Route(path: '', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ImageRepository $imageRepository,
        PaginatorInterface $paginator,
        LoggerInterface $logger
    ): Response {
        /** @var ImageFilterData $filterData */
        $filterData = new ImageFilterData(
            $imageRepository->getEarliestImageCaptureDate(),
            $imageRepository->getLatestImageCaptureDate(),
        );

        // These are overrides that can be sent by links set up on our
        // charts on the Statistics page. If we get a neighbourhood, star
        // rating or start & end dates via those, we override our defaults.
        try {
            $filterData->overrideNeighbourhoodFromUrlParam((string) $request->query->get('neighbourhood'));
            $filterData->overrideRatingFromUrlParam((string) $request->query->get('rating'));
            $filterData->overrideStartDateFromUrlParam((string) $request->query->get('periodStartDate'));
            $filterData->overrideEndDateFromUrlParam((string) $request->query->get('periodEndDate'));
        } catch (Exception $e) {
            // Someone may be trying to fiddle with our URL parameters. Don't fail; the override
            // functions are sensible enough to ignore invalid inputs. But we should log.
            $logger->error(
                'Image controller override parameters caused an exception to be thrown. Quietly ignoring them.',
                [
                    'exception_message' => $e->getMessage()
                ]
            );
        }

        // Filtering form for the top of the page
        $neighbourhoodChoices = $this->getNeighbourhoodChoices($imageRepository);
        $filterForm = $this->createForm(
            ImageFilterType::class,
            $filterData,
            [
                'neighbourhoods' => array_combine($neighbourhoodChoices, array_values($neighbourhoodChoices)),
                'csrf_protection' => false, // We're just a GET request, and nothing bad happens no matter what you do.
                'attr' => [
                    'data-controller' => 'imagefilter'
                ]
            ]
        );

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filterData = $filterForm->getData();
        };

        $qb = $imageRepository->getReversePaginatorQueryBuilder();

        $this->filterQuery($filterData, $qb);

        $query = $qb->getQuery();

        $page = $request->query->getInt('page', 1);
        $pagination = $paginator->paginate(
            $query,
            $page,
            20
        );

        return $this->render('image/index.html.twig', [
            'image_pagination' => $pagination,
            'filter_form' => $filterForm->createView()
        ]);
    }

    /**
     * @return array<string>
     */
    private function getNeighbourhoodChoices(ImageRepository $imageRepository)
    {
        return $imageRepository->getAllNeighbourhoods();
    }

    private function filterQuery(ImageFilterData $filterData, QueryBuilder $qb): void
    {
        if ($filterData->hasRating()) {
            $this->filterQueryByRating(
                $filterData->getRating(),
                $filterData->getRatingComparison(),
                $qb
            );
        }

        if ($filterData->hasStartDate()) {
            $qb
                ->andWhere('i.capturedAt >= :startDate')
                ->setParameter('startDate', $filterData->getStartDate());
        }

        if ($filterData->hasEndDate()) {
            $endDate = (new CarbonImmutable($filterData->getEndDate()))->addDays(1);
            $qb
                ->andWhere('i.capturedAt < :endDate')
                ->setParameter('endDate', $endDate);
        }

        if ($filterData->hasNeighbourhood()) {
            $qb
                ->andWhere('i.neighbourhood = :neighbourhood')
                ->setParameter('neighbourhood', $filterData->getNeighbourhood());
        }

    }

    private function filterQueryByRating(?int $rating, string $ratingComparison, QueryBuilder &$qb): QueryBuilder
    {
        if ($rating !== null) {
            match ($ratingComparison) {
                'lte' => $qb->andWhere($qb->expr()->lte('i.rating', ':rating')),
                'gte' => $qb->andWhere($qb->expr()->gte('i.rating', ':rating')),
                // 'eq'
                default => $qb->andWhere($qb->expr()->eq('i.rating', ':rating')),
            };
            $qb->setParameter('rating', $rating);
        }
        return $qb;
    }
}
