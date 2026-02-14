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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class WanderController extends AbstractController
{
    public function __construct(private readonly \App\Repository\WanderRepository $wanderRepository, private readonly \Knp\Component\Pager\PaginatorInterface $paginator, private readonly \App\Repository\ImageRepository $imageRepository)
    {
    }

    #[Route(path: '/wanders.{_format}', name: 'wanders_index', requirements: ['_format' => 'html'], methods: ['GET'], condition: "'application/json' not in request.getAcceptableContentTypes()", format: 'html')]
    public function index(
        Request $request
        ): Response
    {
        $qb = $this->wanderRepository->wandersWithImageCountQueryBuilder();
        $query = $qb->getQuery();
        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20 // Items per page
        );

        return $this->render('wander/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/wanders/{id}', name: 'wanders_show', methods: ['GET'], condition: "'application/json' not in request.getAcceptableContentTypes()")]
    public function show(
        Request $request,
        Wander $wander): Response
    {
        $prev = $this->wanderRepository->findPrev($wander);
        $next = $this->wanderRepository->findNext($wander);
        $page = $request->query->getInt('page', 1);
        if ($page === 0 || $page > 1024) {
            // Someone's probably trying a SQL injection attack or something. I prefer just to
            // ignore them rather than having the pager throw an exception when someone asks for
            // page 0. Redirect back here but without the dodgy parameter.
            return $this->redirectToRoute($request->attributes->get('_route'), ['id' => $wander->getId()]);
        }

        $paginatorQuery = $this->imageRepository->getPaginatorQueryBuilder($wander)->getQuery();

        $pagination = $this->paginator->paginate(
            $paginatorQuery,
            $page,
            20, // Per page
        );

        return $this->render('wander/show.html.twig', [
            'wander' => $wander,
            'image_pagination' => $pagination,
            'prev' => $prev,
            'next' => $next
        ]);
    }

    /**
     *
     * RSS, etc. feeds
     */
    #[Route(path: '/feed.{!_format}', name: 'feed', requirements: ['_format' => 'rss2|atom'], methods: ['GET'], format: 'rss2')]
    public function feed(Request $request): Response
    {
        $qb = $this->wanderRepository
            ->standardQueryBuilder()
            ->setMaxResults(20);

        $wanders = $qb->getQuery()->getResult();
        $format = $request->getRequestFormat();

        return $this->render('wander/feed.' . $format . '.twig', [
            'wanders' => $wanders
        ]);
    }
}
