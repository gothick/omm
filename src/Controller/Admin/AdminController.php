<?php

namespace App\Controller\Admin;

use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    public function __construct(private readonly \App\Service\StatsService $statsService, private readonly \Symfony\Contracts\Cache\TagAwareCacheInterface $cache)
    {
    }

    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        $wanderStats = $this->statsService->getWanderStats();
        $imageStats = $this->statsService->getImageStats();
        $imageNeighbourhoodStats = $this->statsService->getImageNeighbourhoodStats();
        return $this->render('admin/index.html.twig', [
            'imageStats' => $imageStats,
            'wanderStats' => $wanderStats,
            'imageNeighbourhoodStats' => $imageNeighbourhoodStats
        ]);
    }

    #[Route(path: '/clearStatsCache', name: 'clear_stats_cache')]
    public function clearStatsCache(Request $request): Response
    {
        if ($this->isCsrfTokenValid('admin_clear_stats_cache', (string) $request->request->get('_token'))) {
            $this->cache->invalidateTags(['stats']);
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
