<?php

namespace App\Controller;

use App\Service\StatsService;
use phpGPX\Models\Stats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'home')]
    public function index(StatsService $statsService): Response
    {
        return $this->render('home/index.html.twig', [
            'wanderStats' => $statsService->getWanderStats(),
            'imageStats' => $statsService->getImageStats()
        ]);
    }
}
