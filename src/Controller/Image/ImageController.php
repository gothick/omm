<?php

namespace App\Controller\Image;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        // TODO: Add many interesting parameters, etc.
        $query = $qb->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('image/index.html.twig', [
            'image_pagination' => $pagination
        ]);
    }

    /**
     * @Route(
     *  "/rated/{rating}",
     *  name="rated",
     *  methods={"GET"},
     *  requirements={"rating"="\d+"}
     * )
     */
    public function rated(
        Request $request,
        ImageRepository $imageRepository,
        PaginatorInterface $paginator,
        int $rating
    ): Response {
        $qb = $imageRepository->getReversePaginatorQueryBuilder();
        $qb
            ->andWhere('i.rating = :rating')
            ->setParameter('rating', $rating);

        $query = $qb->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('image/index.html.twig', [
            'image_pagination' => $pagination
        ]);
    }
}
