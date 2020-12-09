<?php

namespace App\Controller\Wander;

use App\Entity\Wander;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/wander", name="wander_")
 * 
 */
class WanderController extends AbstractController
{
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