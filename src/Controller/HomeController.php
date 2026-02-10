<?php

namespace App\Controller;

use App\Service\StatsService;
use phpGPX\Models\Stats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(private readonly \App\Service\StatsService $statsService)
    {
    }

    #[Route(path: '/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'wanderStats' => $this->statsService->getWanderStats(),
            'imageStats' => $this->statsService->getImageStats()
        ]);
    }
}
