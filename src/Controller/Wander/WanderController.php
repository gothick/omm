<?php

namespace App\Controller\Wander;

use App\Entity\Wander;
use App\Repository\WanderRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/wanders", name="wanders_")
 *
 */
class WanderController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(
        Request $request,
        WanderRepository $wanderRepository,
        PaginatorInterface $paginator
        ): Response
    {
        // Customise the query to add an imageCount built-in so we can efficiently
        // (and at all :) ) sort it in our paginator.
        $qb = $wanderRepository
            ->standardQueryBuilder()
            ->select('w AS wander')
            ->addSelect('COUNT(i) AS imageCount')
            ->leftJoin('w.images', 'i')
            ->groupBy('w');

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
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(Wander $wander): Response
    {
        return $this->render('/wander/show.html.twig', [
            'wander' => $wander,
        ]);
    }
}