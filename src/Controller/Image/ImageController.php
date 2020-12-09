<?php

namespace App\Controller\Image;

use App\Entity\Image;
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
    public function show(Image $image): Response
    {
        return $this->render('/image/show.html.twig', [
            'image' => $image,
        ]);
    }
}
