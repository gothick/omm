<?php

namespace App\Controller;

use App\Entity\Wander;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/wanders/testadd", name="testadd_wander")
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

    /**
     * @Route("/wanders/show/{id}", name="wander_show")
     */
    public function showWander(int $id): Response
    {
        $wander = $this->getDoctrine()->getRepository(Wander::class)->find($id);
        if (!$wander) {
            throw $this->createNotFoundException('No wander found for id' . $id);
        }
        return $this->render('wander/show.html.twig', [
            'wander' => $wander
        ]);
    }

    /**
     * @Route("/wanders/new", name="new_wander")
     */
    public function new(Request $request) : Response {
        $wander = new Wander();
        /*
        $wander->setTitle("I'm a wander.");
        $wander->setStartTime(new \DateTime());
        $wander->setEndTime(new \DateTime());
        $wander->setDescription("I'm a soulful description of a wander in Hotwells");
        */

        $form = $this->createFormBuilder($wander)
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)
            ->add('startTime', DateTimeType::class)
            ->add('endTime', DateTimeType::class)
            ->add('gpxFilename', FileType::class)
            ->add('save', SubmitType::class, ['label' => 'Create Wander'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $wander = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($wander);
            $entityManager->flush();
            return $this->redirectToRoute('wander_show', ['id' => $wander->getId()]);
        }

        return $this->render('wander/new.html.twig', [
            'form' => $form->createView(),
        ]);    
    }
}
