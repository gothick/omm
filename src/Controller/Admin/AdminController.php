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
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(
        StatsService $statsService
    ): Response {
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
    public function clearStatsCache(Request $request, TagAwareCacheInterface $cache): Response
    {
        if ($this->isCsrfTokenValid('admin_clear_stats_cache', (string) $request->request->get('_token'))) {
            $cache->invalidateTags(['stats']);
            $this->addFlash(
                'notice',
                'Stats Cache Cleared.'
            );
        } else {
            $this->addFlash(
                'error',
                'Stats not cleared. Invalid Csrf token.'
            );
        }
        return $this->redirectToRoute('admin_index');
    }
}