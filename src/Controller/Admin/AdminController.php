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
        StatsService $statsService,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $wanderStats = $statsService->getWanderStats();
        $imageStats = $statsService->getImageStats();

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            'datasets' => [
                [
                    'label' => 'My First dataset',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [0, 10, 5, 2, 20, 30, 45],
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                   'suggestedMin' => 0,
                   'suggestedMax' => 100,
                ],
            ],
        ]);

        return $this->render('admin/index.html.twig', [
            'imageStats' => $imageStats,
            'wanderStats' => $wanderStats,
            'chart' => $chart
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