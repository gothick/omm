<?php

namespace App\Controller\Wander;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use App\Service\SettingsService;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class WanderController extends AbstractController
{
    /**
     * @Route("/wanders", name="wanders_index", methods={"GET"})
     */
    public function index(
        Request $request,
        WanderRepository $wanderRepository,
        PaginatorInterface $paginator
        ): Response
    {
        $qb = $wanderRepository->wandersWithImageCountQueryBuilder();
        $query = $qb->getQuery();
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20 // Items per page
        );

        return $this->render('wander/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     *
     * RSS, etc. feeds
     *
     * @Route(
     *  "/feed.{!_format}",
     *  name="feed",
     *  methods={"GET"},
     *  format="rss2",
     *  requirements={
     *      "_format": "rss2|atom"
     *  }
     * )
     */
    public function feed(Request $request, WanderRepository $wanderRepository): Response
    {
        $qb = $wanderRepository
            ->standardQueryBuilder()
            ->setMaxResults(20);

        $wanders = $qb->getQuery()->getResult();
        $format = $request->getRequestFormat();

        return $this->render('wander/feed.' . $format . '.twig', [
            'wanders' => $wanders
        ]);
    }

    /**
     * @Route("/wanders/{id}", name="wanders_show", methods={"GET"})
     */
    public function show(
        Request $request,
        Wander $wander,
        WanderRepository $wanderRepository,
        ImageRepository $imageRepository,
        PaginatorInterface $paginator): Response
    {
        $prev = $wanderRepository->findPrev($wander);
        $next = $wanderRepository->findNext($wander);

        $paginatorQuery = $imageRepository->getPaginatorQuery($wander);

        $pagination = $paginator->paginate(
            $paginatorQuery,
            $request->query->getInt('page', 1),
            20, // Per page
        );

        return $this->render('wander/show.html.twig', [
            'wander' => $wander,
            'image_pagination' => $pagination,
            'prev' => $prev,
            'next' => $next
        ]);
    }
}