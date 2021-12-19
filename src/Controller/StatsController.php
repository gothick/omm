<?php

namespace App\Controller;

use App\Service\StatsService;
use Colors\RandomColor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatsController extends AbstractController
{
    const WANDER_NUMBER_COLOUR = '#2491B3';
    const WANDER_DISTANCE_COLOUR = '#ffb266';
    const IMAGES_NUMBER_COLOUR = '#BF5439';
    /**
     * @Route("/stats", name="stats_index")
     */
    public function index(
        StatsService $statsService,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $wanderStats = $statsService->getWanderStats();
        $imageStats = $statsService->getImageStats();
        $imageLocationStats = $statsService->getImageLocationStats();

        $wanderDataSeries =
            [
                [
                    'label' => 'Number of Wanders',
                    'colour' => self::WANDER_NUMBER_COLOUR,
                    'extractFunction' => fn($dp): int => $dp['numberOfWanders'],
                ],
                [
                    'label' => 'Distance Walked (km)',
                    'colour' => self::WANDER_DISTANCE_COLOUR,
                    'extractFunction' => fn($dp): string => number_format($dp['totalDistance'] / 1000.0, 2)
                ]
            ];

        $monthlyWanderChart = $chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData($this->generatePeriodicChartData($wanderStats['monthlyStats'], $wanderDataSeries));

        $yearlyWanderChart = $chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData($this->generatePeriodicChartData($wanderStats['yearlyStats'], $wanderDataSeries));

        $imageDataSeries =
            [
                [
                    'label' => 'Photos Taken',
                    'colour' => self::IMAGES_NUMBER_COLOUR,
                    'extractFunction' => fn($dp): int => $dp['numberOfImages'],
                ]
            ];

        $monthlyImagesChart = $chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData($this->generatePeriodicChartData($wanderStats['monthlyStats'], $imageDataSeries));

        $yearlyImagesChart = $chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData($this->generatePeriodicChartData($wanderStats['yearlyStats'], $imageDataSeries));

        $locationsChart = $chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setOptions([
                'indexAxis' => 'y',
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ]
                ]
            ])
            ->setData([
                'labels' => array_keys($imageLocationStats),
                'datasets' => [
                    [
                        'label' => 'Number of Photos',
                        'backgroundColor' => RandomColor::many(count($imageLocationStats)),
                        'borderColor' => 'black',
                        'borderWidth' => 1,
                        'borderRadius' => 5,
                        'data' => array_values($imageLocationStats)
                    ]
                ]
            ]);

        return $this->render('stats/index.html.twig', [
            'controller_name' => 'StatsController', // TODO: Remove this boilerplate
            'imageStats' => $imageStats,
            'wanderStats' => $wanderStats,
            'imageLocationStats' => $imageLocationStats,
            'monthlyWanderChart' => $monthlyWanderChart,
            'yearlyWanderChart' => $yearlyWanderChart,
            'monthlyImagesChart' => $monthlyImagesChart,
            'yearlyImagesChart' => $yearlyImagesChart,
            'locationsChart' => $locationsChart
        ]);
    }

    /**
     * @param array<string, mixed> $sourceStats
     * @param array<int, array<string, mixed>> $seriesDefinitions
     * @return array<string, mixed>
     */
    private function generatePeriodicChartData(
        array $sourceStats,
        array $seriesDefinitions
    ): array {
        $data = [
            'labels' => array_map(fn($dp): string => $dp['periodLabel'], $sourceStats)
        ];
        foreach ($seriesDefinitions as $series) {
            $data['datasets'][] = [
                'label' => $series['label'],
                'backgroundColor' => $series['colour'],
                'borderColor' => 'black',
                'borderWidth' => 1,
                'borderRadius' => 5,
                'data' => array_map($series['extractFunction'], $sourceStats)
            ];
        }
        return $data;
    }
}
