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
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/image", name="image_")
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
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

    /**
     * @Route(
     *  "",
     *  name="index",
     *  methods={"GET"},
     * )
     */
    public function index(
        Request $request,
        ImageRepository $imageRepository,
        PaginatorInterface $paginator
    ): Response {
        /** @var ImageFilterData $filterData */

        // Sensible defaults
        $filterData = new ImageFilterData();
        $filterData->setStartDate($imageRepository->getEarliestImageCaptureDate());
        $filterData->setEndDate($imageRepository->getLatestImageCaptureDate());
        $filterData->setRatingComparison('eq');
        $locationChoices = $this->getLocationChoices($imageRepository);

        // These are overrides that can be sent by links set up on our
        // charts on the Statistics page. If we get a location, star
        // rating or start & end dates via those, we override our defaults.
        $filterData->setLocation((string) $request->query->get('location'));
        if ($request->query->has('rating')) {
            $filterData->setRating($request->query->getInt('rating'));
        }
        if ($request->query->has('periodStartDate')) {
            $filterData->setStartDateFromUrlParam((string) $request->query->get('periodStartDate'));
        }
        if ($request->query->has('periodEndDate')) {
            $filterData->setEndDateFromUrlParam((string) $request->query->get('periodEndDate'));
        }

        // Filtering form for the top of the page
        $filterForm = $this->createForm(
            ImageFilterType::class,
            $filterData,
            [
                'locations' => array_combine($locationChoices, array_values($locationChoices)),
                'csrf_protection' => false // We're just a GET request, and nothing bad happens no matter what you do.
            ]
        );

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filterData = $filterForm->getData();
        };

        $qb = $imageRepository->getReversePaginatorQueryBuilder();

        if ($filterData->hasRating() && $filterData->hasRatingComparison()) {
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

        if ($filterData->hasLocation()) {
            $qb
                ->andWhere('i.location = :location')
                ->setParameter('location', $filterData->getLocation());
        }

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
    private function getLocationChoices(ImageRepository $imageRepository)
    {
        return $imageRepository->getAllLocations();
    }

    private function filterQueryByRating(?int $rating, string $ratingComparison, QueryBuilder &$qb): QueryBuilder
    {
        if ($rating !== null) {
            switch ($ratingComparison) {
                case 'lte':
                    $qb->andWhere($qb->expr()->lte('i.rating', ':rating'));
                    break;
                case 'gte':
                    $qb->andWhere($qb->expr()->gte('i.rating', ':rating'));
                    break;
                default:
                    // 'eq'
                    $qb->andWhere($qb->expr()->eq('i.rating', ':rating'));
                    break;
            }
            $qb->setParameter('rating', $rating);
        }
        return $qb;
    }
}
