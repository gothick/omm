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
    ): Response {
        $wanderStats = $statsService->getWanderStats();
        $imageStats = $statsService->getImageStats();

        $wanderNumberColour = '#2491B3';
        $wanderDistanceColour = '#ffb266';

        $imagesNumberColour = '#BF5439';

        $monthlyWanderChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $monthlyWanderChart->setData([
            'labels' => array_map(fn($dp): string => $dp['periodLabel'], $wanderStats['monthlyStats']),
            'datasets' => [
                // TODO: These colours should be defined in CSS. Add class somehow?
                [
                    'label' => 'Number of Wanders',
                    'backgroundColor' => $wanderNumberColour,
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): int => $dp['numberOfWanders'], $wanderStats['monthlyStats']),
                ],
                [
                    'label' => 'Distance Walked (km)',
                    'backgroundColor' => $wanderDistanceColour,
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): string => number_format($dp['totalDistance'] / 1000.0, 2), $wanderStats['monthlyStats']),
                ]
            ]
        ]);

        $yearlyWanderChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $yearlyWanderChart->setData([
            'labels' => array_map(fn($dp): string => $dp['periodLabel'], $wanderStats['yearlyStats']),
            'datasets' => [
                // TODO: These colours should be defined in CSS. Add class somehow?
                [
                    'label' => 'Number of Wanders',
                    'backgroundColor' => $wanderNumberColour,
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): int => $dp['numberOfWanders'], $wanderStats['yearlyStats']),
                ],
                [
                    'label' => 'Distance Walked (km)',
                    'backgroundColor' => $wanderDistanceColour,
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): string => number_format($dp['totalDistance'] / 1000.0, 2), $wanderStats['yearlyStats']),
                ]
            ]
        ]);

        $monthlyImagesChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $monthlyImagesChart->setData([
            'labels' => array_map(fn($dp): string => $dp['periodLabel'], $wanderStats['monthlyStats']),
            'datasets' => [
                // TODO: These colours should be defined in CSS. Add class somehow?
                [
                    'label' => 'Photos Taken',
                    'backgroundColor' => $imagesNumberColour, // '#ffb266',
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): int => $dp['numberOfImages'], $wanderStats['monthlyStats']),
                ]
            ]
        ]);

        $yearlyImagesChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $yearlyImagesChart->setData([
            'labels' => array_map(fn($dp): string => $dp['periodLabel'], $wanderStats['yearlyStats']),
            'datasets' => [
                // TODO: These colours should be defined in CSS. Add class somehow?
                [
                    'label' => 'Photos Taken',
                    'backgroundColor' => $imagesNumberColour, // '#ffb266',
                    'borderColor' => 'black',
                    'data' => array_map(fn($dp): int => $dp['numberOfImages'], $wanderStats['yearlyStats']),
                ]
            ]
        ]);

        return $this->render('stats/index.html.twig', [
            'controller_name' => 'StatsController', // TODO: Remove this boilerplate
            'imageStats' => $imageStats,
            'wanderStats' => $wanderStats,
            'monthlyWanderChart' => $monthlyWanderChart,
            'yearlyWanderChart' => $yearlyWanderChart,
            'monthlyImagesChart' => $monthlyImagesChart,
            'yearlyImagesChart' => $yearlyImagesChart
        ]);
    }
}
