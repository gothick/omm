<?php

namespace App\Controller\Admin;

use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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

    /**
     * @Route("/clearStatsCache", name="clear_stats_cache")
     */
    public function clearStatsCache(Request $request, TagAwareCacheInterface $cache) 
    {
        if ($this->isCsrfTokenValid('admin_clear_stats_cache', $request->request->get('_token'))) {
            $cache->invalidateTags(['stats']);
            $this->addFlash(
                'notice',
                'Stats Cache Cleared.'
            );
            return $this->redirectToRoute('admin_index');
        }
    }
}