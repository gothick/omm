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

        $year = intval($request->query->get('year', 0));
        if ($year < 1900 || $year > 5000) {
            $year = null;
        }

        $month = $request->query->getInt('month', 0);
        if ($month < 1 || $month > 12) {
            $month = null;
        }

        $rating = $request->query->getInt('rating', -1);
        if ($rating === -1) {
            $rating = null;
        }

        $ratingCompare = (string) $request->query->get('rating_compare', 'eq');

        $qb = $imageRepository->getReversePaginatorQueryBuilder();

        $this->filterQueryByYearAndMonth($year, $month, $qb);
        $this->filterQueryByRating($rating, $ratingCompare, $qb);

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

    private function filterQueryByYearAndMonth(?int $year, ?int $month, QueryBuilder &$qb): QueryBuilder
    {
        // Our year parameter can be entirely missing, or "any", as well as an
        // integer year.
        if ($year !== null) { // Don't want to send any implausible years to MySQL
            // We can optionally have a month to limit the date range
            // even further.
            if ($month === null) {
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

    private function filterQueryByRating(?int $rating, string $ratingCompare, QueryBuilder &$qb): QueryBuilder
    {
        if ($rating !== null) {
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
