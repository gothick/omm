<?php

namespace App\Controller;

use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatsController extends AbstractController
{
    /**
     * @Route("/stats", name="stats_index")
     */
    public function index(
        StatsService $statsService,
        ChartBuilderInterface $chartBuilder
    ): Response
    {
        $wanderStats = $statsService->getWanderStats();
        $imageStats = $statsService->getImageStats();

        $monthlyChart = $this->buildPeriodicChart($wanderStats['monthlyStats'], $chartBuilder);
        $yearlyChart = $this->buildPeriodicChart($wanderStats['yearlyStats'], $chartBuilder);

        return $this->render('stats/index.html.twig', [
            'controller_name' => 'StatsController', // TODO: Remove this boilerplate
            'imageStats' => $imageStats,
            'wanderStats' => $wanderStats,
            'monthlyChart' => $monthlyChart,
            'yearlyChart' => $yearlyChart
        ]);
    }
    /**
     * Builds the data for a periodic Chartjs stats chart, e.g. wanders/distance/etc per month or year.
     *
     * @return Chart
     * @param array<string, mixed> $periodicStats
     * @param ChartBuilderInterface $chartBuilder
     */
    private function buildPeriodicChart(
        array $periodicStats,
        ChartBuilderInterface $chartBuilder
    ): Chart {
        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => array_map(fn($dp): string => $dp['periodLabel'], $periodicStats),
            'datasets' => [
                // TODO: These colours should be defined in CSS. Add class somehow?
                [
                    'label' => 'Number of Wanders',
                    'backgroundColor' => '#ff66b2',
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): int => $dp['numberOfWanders'], $periodicStats),
                ],
                [
                    'label' => 'Distance Walked (km)',
                    'backgroundColor' => '#66ffff',
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): string => number_format($dp['totalDistance'] / 1000.0, 2), $periodicStats),
                ],
                [
                    'label' => 'Photos Taken',
                    'backgroundColor' => '#ffb266',
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): int => $dp['numberOfImages'], $periodicStats),
                ]
            ]
        ]);
        return $chart;
    }
}

