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

        // TODO: This doesn't work, as e.g. keywords being an empty array
        //
        $problems = $qb
            ->join('w.images', 'i')
            ->select('w AS wander')
            ->addSelect('SUM(CASE WHEN i.title IS NULL THEN 1 ELSE 0 END) AS no_title')
            ->addSelect('SUM(CASE WHEN i.latlng IS NULL THEN 1 ELSE 0 END) AS no_latlng')
            ->addSelect('SUM(CASE WHEN i.rating IS NULL OR i.rating = 0 THEN 1 ELSE 0 END) AS no_rating')
            // TODO: This is a hideous bodge and will break when we finally give in and move
            // keywords and auto-tags to being related entities rather than a dirty PHP
            // array, but it's good enough for a problem admin page for now.
            ->addSelect("SUM(CASE WHEN i.keywords IS NULL OR i.keywords = 'a:0:{}' THEN 1 ELSE 0 END) AS no_keywords")
            ->addSelect("SUM(CASE WHEN i.auto_tags IS NULL OR i.auto_tags = 'a:0:{}' THEN 1 ELSE 0 END) AS no_auto_tags")

            ->addSelect(
                "(SUM(CASE WHEN i.title IS NULL THEN 1 ELSE 0 END)) + " .
                "(SUM(CASE WHEN i.latlng IS NULL THEN 1 ELSE 0 END)) + " .
                "(SUM(CASE WHEN i.rating IS NULL OR i.rating = 0 THEN 1 ELSE 0 END)) + " .
                "(SUM(CASE WHEN i.keywords IS NULL OR i.keywords = 'a:0:{}' THEN 1 ELSE 0 END)) + " .
                "(SUM(CASE WHEN i.auto_tags IS NULL OR i.auto_tags = 'a:0:{}' THEN 1 ELSE 0 END)) AS total_problems")
            ->addSelect(
                "(SUM(CASE WHEN i.title IS NULL THEN 1 ELSE 0 END)) + " .
                "(10 * SUM(CASE WHEN i.latlng IS NULL THEN 1 ELSE 0 END)) + " .
                "(5 * SUM(CASE WHEN i.rating IS NULL OR i.rating = 0 THEN 1 ELSE 0 END)) + " .
                "(0.01 * SUM(CASE WHEN i.keywords IS NULL OR i.keywords = 'a:0:{}' THEN 1 ELSE 0 END)) + " .
                "(0.001 * SUM(CASE WHEN i.auto_tags IS NULL OR i.auto_tags = 'a:0:{}' THEN 1 ELSE 0 END)) AS weighted_problem_score")
            ->addGroupBy('w')
            ->having('no_title > 0')
            ->orHaving('no_latlng > 0')
            ->orHaving('no_keywords > 0')
            ->orHaving('no_auto_tags > 0')
            ->orderBy('weighted_problem_score', 'desc')
            ->getQuery()
            ->getResult();

        // TODO: Orphans

        return $this->render('/admin/problems/index.html.twig', [
            'problems' => $problems
        ]);
    }

    // TODO: These all share a lot of things in common, but I'm not sure
    // how much this is going to get used, so I'm keeping it fairly brain-dead
    // for now. Revisit later.

    /**
     * @Route("/no_title/wander/{id}", name="no_title", methods={"GET"})
     */
    public function no_title(Wander $wander): Response {
        return $this->render('/admin/problems/no_title.html.twig', [
            'wander' => $wander
        ]);
    }
    /**
     * @Route("/no_latlng/wander/{id}", name="no_latlng", methods={"GET"})
     */
    public function no_latlng(Wander $wander): Response {
        return $this->render('/admin/problems/no_latlng.html.twig', [
            'wander' => $wander
        ]);
    }
    /**
     * @Route("/no_rating/wander/{id}", name="no_rating", methods={"GET"})
     */
    public function no_rating(Wander $wander): Response {
        return $this->render('/admin/problems/no_rating.html.twig', [
            'wander' => $wander
        ]);
    }

}
