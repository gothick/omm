<?php

namespace App\Controller;

use App\Service\StatsService;
use Colors\RandomColor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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

    /** @var UrlGeneratorInterface */
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

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

        $imageDataSeries = [];
        for ($rating = 0; $rating <= 5; $rating++) {
            $imageDataSeries[] = [
                'label' => "Photos (Rated: $rating stars)",
                'colour' => self::IMAGES_COLOUR_STACK[$rating],
                'extractFunction' => fn($dp): int => $dp['numberOfImagesByRating'][$rating],
                'rating' => $rating
            ];
        }

        $monthlyImagesChart = $chartBuilder
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

        $yearlyImagesChart = $chartBuilder
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

        $locationsChart = $chartBuilder
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
                'labels' => array_keys($imageLocationStats),
                'urls' => array_map(fn($l): string => $this->router->generate('image_index', ['location' => $l]), array_keys($imageLocationStats)),
                'datasets' => [
                    [
                        'label' => 'Number of Photos',
                        'backgroundColor' => RandomColor::many(count($imageLocationStats)),
                        'borderColor' => 'black',
                        'borderWidth' => 1,
                        'borderRadius' => 5,
                        'data' => array_values($imageLocationStats),
                    ]
                ]
            ]);

        return $this->render('stats/index.html.twig', [
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
                        'year' => $dp['year']
                    ];
                    if ($dp['periodType'] === 'month') {
                        $params['month'] = $dp['month'];
                    }
                    return $this->router->generate('image_index', $params);
                }, $sourceStats);
            }
        }
        return $data;
    }
}
