<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/experimental', name: 'experimental_')]
class ExperimentalController extends AbstractController
{
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('experimental/index.html.twig');
    }
    #[Route(path: '/imagecluster', name: 'imagecluster', methods: ['GET'])]
    public function cluster(): Response
    {
        return $this->render('experimental/imagecluster.html.twig', [
        ]);
    }
}

