<?php

namespace App\Controller\Image;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/images", name="image_")
 *
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(Image $image, ImageRepository $imageRepository): Response
    {
        $nextImage = $imageRepository->findNextByCapturedAtAndId($image->getCapturedAt(), $image->getId());
        $prevImage = $imageRepository->findPrevByCapturedAtAndId($image->getCapturedAt(), $image->getId());

        return $this->render('/image/show.html.twig', [
            'image' => $image,
            'next_image' => $nextImage,
            'prev_image' => $prevImage
        ]);
    }
}
