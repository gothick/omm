<?php

namespace App\Controller\Image;

use App\Entity\Image;
use App\Form\ImageFilterType;
use App\Repository\ImageRepository;
use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
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
     * @Route("", name="index", methods={"GET"})
     */
    public function index(
        Request $request,
        ImageRepository $imageRepository,
        PaginatorInterface $paginator
    ): Response {

        $qb = $imageRepository->getReversePaginatorQueryBuilder();

        $this->filterQueryByYearAndMonth($request->query, $qb);
        $this->filterQueryByRating($request->query, $qb);

        if ($request->query->has('location')) {
            $qb->andWhere('i.location = :location')
                ->setParameter('location', $request->query->get('location'));
        }

        $query = $qb->getQuery();

        $page = $request->query->getInt('page', 1);
        $pagination = $paginator->paginate(
            $query,
            $page,
            20
        );

        return $this->render('image/index.html.twig', [
            'image_pagination' => $pagination
        ]);
    }

    private function filterQueryByYearAndMonth(InputBag $params, QueryBuilder &$qb): QueryBuilder
    {
        if ($params->has('year')) {
            /** @var int $year */
            $year = $params->getInt('year', (int) date("Y"));
            // We can optionally have a month to limit the date range
            // even further.
            $month = $params->getInt('month', 0);
            if ($month < 1 || $month > 12) {
                // Just the year
                $startDate = Carbon::create($year, 1, 1);
                $endDate = Carbon::create($year + 1, 1, 1);
            } else {
                // We're looking at a particular month of the year
                /** @var Carbon $startDate */
                $startDate = Carbon::create($year, $month, 1);
                $endDate = $startDate->copy()->addMonths(1);
            }
            $qb->andWhere('i.capturedAt >= :startDate')
                ->setParameter('startDate', $startDate)
                ->andWhere('i.capturedAt < :endDate')
                ->setParameter('endDate', $endDate);
        }
        return $qb;
    }

    private function filterQueryByRating(InputBag $params, QueryBuilder &$qb): QueryBuilder
    {
        $rating = $params->getInt('rating', -1);
        if ($rating >= 0 && $rating <= 5) {
            $ratingCompare = $params->get('rating_compare', 'eq');
            switch ($ratingCompare) {
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
