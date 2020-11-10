<?php

namespace App\Controller;

use App\Entity\Wander;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WanderController extends AbstractController
{
    /**
     * @Route("/wanders", name="wander")
     */
    public function index(): Response
    {
        return $this->render('wander/index.html.twig', [
            'controller_name' => 'WanderController',
        ]);
    }

    /**
     * @Route("/wanders/testadd", name="create_wander")
     */
    public function createWander(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $wander = new Wander();
        $wander->setTitle("I'm a wander.");
        $wander->setStartTime(new \DateTime());
        $wander->setEndTime(new \DateTime());
        $wander->setDescription("I'm a soulful description of a wander in Hotwells");
        $entityManager->persist($wander);
        $entityManager->flush();
        return new Response("Saved a new wander with id " . $wander->getId());
    }
}
