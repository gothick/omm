<?php

namespace App\Controller\Admin;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\WanderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/problems", name="admin_problems_")
 */
class ProblemController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(WanderRepository $wanderRepository): Response
    {
        $qb = $wanderRepository->createQueryBuilder('w');
        $problems = $qb
            ->join('w.images', 'i')
            ->select('w AS wander')
            ->addSelect('SUM(CASE WHEN i.title IS NULL THEN 1 ELSE 0 END) AS no_title')
            ->addSelect('SUM(CASE WHEN i.latlng IS NULL THEN 1 ELSE 0 END) AS no_latlng')
            ->addSelect('SUM(CASE WHEN i.keywords IS NULL THEN 1 ELSE 0 END) AS no_keywords')
            ->addSelect('SUM(CASE WHEN i.auto_tags IS NULL THEN 1 ELSE 0 END) AS no_auto_tags')
            ->addGroupBy('w')
            ->having('no_title > 0')
            ->orHaving('no_latlng > 0')
            ->orHaving('no_keywords > 0')
            ->orHaving('no_auto_tags > 0')
            ->getQuery()
            ->getResult();

        return $this->render('/admin/problems/index.html.twig', [
            'problems' => $problems
        ]);
    }
}
