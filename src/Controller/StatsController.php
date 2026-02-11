<?php

namespace App\Controller;

use App\Service\StatsService;
use Colors\RandomColor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatsController extends AbstractController
{
    const WANDER_NUMBER_COLOUR = '#2491B3';

    const WANDER_DISTANCE_COLOUR = '#ffb266';

    const IMAGES_NUMBER_COLOUR = '#ae411e';

    const IMAGES_COLOUR_STACK = [
        // Generated from a couple of our base colours using
        // Aquarelo https://setapp.com/apps/aquarelo
        '#000000', // 0 star  -- shouldn't really be any of these, as that means "unrated"
        '#940606', // 1 star sangria: ;
        '#ae411e', // 2 star dark-venetian-red: ;
        '#c97d36', // 3 star light-bronze: ;
        '#e4b84e', // 4 star anzac: ;
        '#fff466', // 5 star $yellowish: ;
    ];

    public function __construct(private readonly UrlGeneratorInterface $router, private readonly \App\Service\StatsService $statsService, private readonly \Symfony\UX\Chartjs\Builder\ChartBuilderInterface $chartBuilder)
    {
    }

    #[Route(path: '/stats', name: 'stats_index')]
    public function index(): Response
    {
        $wanderStats = $this->statsService->getWanderStats();
        $imageStats = $this->statsService->getImageStats();
        $imageNeighbourhoodStats = $this->statsService->getImageNeighbourhoodStats();
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
        $monthlyWanderChart = $this->chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData($this->generatePeriodicChartData($wanderStats['monthlyStats'], $wanderDataSeries));
        $yearlyWanderChart = $this->chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData($this->generatePeriodicChartData($wanderStats['yearlyStats'], $wanderDataSeries));
        $imageDataSeries = [];
        for ($rating = 0; $rating <= 5; $rating++) {
            $imageDataSeries[] = [
                'label' => "Photos (Rated: $rating stars)",
                'colour' => self::IMAGES_COLOUR_STACK[$rating],
                'extractFunction' => fn($dp): int => $dp['numberOfImagesByRating'][$rating],
                'rating' => $rating
            ];
        }

        $monthlyImagesChart = $this->chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData($this->generatePeriodicChartData($wanderStats['monthlyStats'], $imageDataSeries))
            ->setOptions([
                'scales' => [
                    'x' => [
                        'stacked' => true
                    ],
                    'y' => [
                        'stacked' => true
                    ]
                ]
            ]);
        $yearlyImagesChart = $this->chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData($this->generatePeriodicChartData($wanderStats['yearlyStats'], $imageDataSeries))
            ->setOptions([
                'scales' => [
                    'x' => [
                        'stacked' => true
                    ],
                    'y' => [
                        'stacked' => true
                    ]
                ]
            ]);
        $neighbourhoodsChart = $this->chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setOptions([
                'maintainAspectRatio' => false,
                'indexAxis' => 'y',
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ]
                ]
            ])
            ->setData([
                'labels' => array_keys($imageNeighbourhoodStats),
                'urls' => array_map(fn($l): string => $this->router->generate('image_index', ['neighbourhood' => $l]), array_keys($imageNeighbourhoodStats)),
                'datasets' => [
                    [
                        'label' => 'Number of Photos',
                        'backgroundColor' => RandomColor::many(count($imageNeighbourhoodStats), [
                            'luminosity' => 'bright',
                            'format' => 'hex'
                        ]),
                        'borderColor' => 'black',
                        'borderWidth' => 1,
                        'borderRadius' => 5,
                        'data' => array_values($imageNeighbourhoodStats),
                    ]
                ]
            ]);
        return $this->render('stats/index.html.twig', [
            'imageStats' => $imageStats,
            'wanderStats' => $wanderStats,
            'imageNeighbourhoodStats' => $imageNeighbourhoodStats,
            'monthlyWanderChart' => $monthlyWanderChart,
            'yearlyWanderChart' => $yearlyWanderChart,
            'monthlyImagesChart' => $monthlyImagesChart,
            'yearlyImagesChart' => $yearlyImagesChart,
            'neighbourhoodsChart' => $neighbourhoodsChart
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
            'labels' => array_map(fn($dp): string => $dp['periodLabel'], $sourceStats),
        ];
        foreach ($seriesDefinitions as $series) {
            $data['datasets'][] = [
                'label' => $series['label'],
                'backgroundColor' => $series['colour'],
                'borderColor' => 'black',
                'borderWidth' => 1,
                'borderRadius' => 5,
                'data' => array_map($series['extractFunction'], $sourceStats),
            ];
            if (array_key_exists('rating', $series)) {
                $data['urls'][] = array_map(function ($dp) use ($series): string {
                    $params = [
                        'rating' => $series['rating'],
                        'periodStartDate' => $dp['periodStartDate'],
                        'periodEndDate' => $dp['periodEndDate']
                    ];
                    return $this->router->generate('image_index', $params);
                }, $sourceStats);
            }
        }

        return $data;
    }
}
