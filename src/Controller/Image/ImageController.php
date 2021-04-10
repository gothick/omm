<?php

namespace App\Controller\Image;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    /**
     * @Route("/image/{id}", name="image_show", methods={"GET"})
     */
    public function show(Image $image, ImageRepository $imageRepository): Response
    {
        $prev = $imageRepository->findPrev($image);
        $next = $imageRepository->findNext($image);
        return $this->render('/image/show.html.twig', [
            'image' => $image,
            'prev' => $prev,
            'next' => $next
        ]);
    }

    /**
     * @Route("/images/{id}", name="old_images_show_redirect", methods={"GET"})
     */
    public function oldImagesShowRedirect(Image $image): Response
    {
        // We used to use /images/ instead of /image/ but changed it
        // because that clashed with the dev firewall rule that didn't
        // do any authentication for /images/ as it's also the
        // /public/images directory.
        // TODO: Go through and remove all the existing image links
        // in the wander and image descriptions that point to
        // /images/ and then remove this.
        return $this->redirectToRoute('image_show', ['id' => $image->getId()], 302);
    }
}
