<?php

namespace App\Controller\Admin;

use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
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
    public function index(ImageRepository $imageRepository, WanderRepository $wanderRepository): Response
    {
        $imageStats = $imageRepository
            ->createQueryBuilder('i') 
            ->select('COUNT(i.id) as totalCount')
            ->addSelect('COUNT(i.latlng) as countWithCoords')
            ->addSelect('COUNT(i.title) as countWithTitle')
            ->addSelect('COUNT(i.description) as countWithDescription')
            ->getQuery()
            ->getOneOrNullResult();

        $wanderStats = $wanderRepository
            ->createQueryBuilder('w') 
            ->select('COUNT(w.id) as totalCount')
            ->addSelect('COUNT(w.title) as countWithTitle')
            ->addSelect('COUNT(w.description) as countWithDescription')
            ->addSelect('SUM(w.duration) as totalDuration')
            ->addSelect('SUM(w.distance) as totalDistance')
            ->addSelect('SUM(w.cumulativeElevationGain) as totalCumulativeElevationGain')
            ->getQuery()
            ->getOneOrNullResult();


        return $this->render('admin/index.html.twig', [
            'imageStats' => $imageStats,
            'wanderStats' => $wanderStats
        ]);
    }
}