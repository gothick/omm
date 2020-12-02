<?php

namespace App\Controller\Admin;

use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(ImageRepository $imageRepository, WanderRepository $wanderRepository, StatsService $statsService): Response
    {
        $wanderStats = $statsService->getWanderStats();
        $imageStats = $statsService->getImageStats();

        return $this->render('admin/index.html.twig', [
            'imageStats' => $imageStats,
            'wanderStats' => $wanderStats
        ]);
    }
}