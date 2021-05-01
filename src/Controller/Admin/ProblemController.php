<?php

namespace App\Controller\Admin;

use App\Entity\Wander;
use App\Repository\ImageRepository;
use App\Repository\ProblemRepository;
use App\Repository\WanderRepository;
use App\Service\ProblemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(
        WanderRepository $wanderRepository,
        ImageRepository $imageRepository,
        ProblemRepository $problemRepository
        ): Response
    {
        // Problems that might take time/resources to find, that are
        // popped into a table on specific demand rather than every
        // time we load this page:
        $builtProblems = $problemRepository->findAll();

        // TODO: Might be sensible to do everything here into the problems
        // table, depending on how much we add to this.
        // Other issues, found on the fly:
        $qb = $wanderRepository->createQueryBuilder('w');

        // TODO: This doesn't work, as e.g. keywords being an empty array
        //
        $problems = $qb
            ->join('w.images', 'i')
            ->select('w AS wander')
            ->addSelect('COUNT(i) AS image_count')
            ->addSelect('SUM(CASE WHEN i.title IS NULL THEN 1 ELSE 0 END) AS no_title')
            ->addSelect('SUM(CASE WHEN i.latlng IS NULL THEN 1 ELSE 0 END) AS no_latlng')
            ->addSelect('SUM(CASE WHEN i.location IS NULL THEN 1 ELSE 0 END) AS no_location')
            ->addSelect('SUM(CASE WHEN i.rating IS NULL OR i.rating = 0 THEN 1 ELSE 0 END) AS no_rating')
            // TODO: This is a hideous bodge and will break when we finally give in and move
            // keywords and auto-tags to being related entities rather than a dirty PHP
            // array, but it's good enough for a problem admin page for now.
            //->addSelect("SUM(CASE WHEN i.keywords IS NULL OR i.keywords = 'a:0:{}' THEN 1 ELSE 0 END) AS no_keywords")
            ->addSelect("SUM(CASE WHEN i.tags is empty THEN 1 ELSE 0 END) AS no_tags")
            ->addSelect("SUM(CASE WHEN i.auto_tags IS NULL OR i.auto_tags = 'a:0:{}' THEN 1 ELSE 0 END) AS no_auto_tags")

            ->addSelect(
                "(SUM(CASE WHEN i.title IS NULL THEN 1 ELSE 0 END)) + " .
                "(SUM(CASE WHEN i.latlng IS NULL THEN 1 ELSE 0 END)) + " .
                "(SUM(CASE WHEN i.location IS NULL THEN 1 ELSE 0 END)) + " .
                "(SUM(CASE WHEN i.rating IS NULL OR i.rating = 0 THEN 1 ELSE 0 END)) + " .
                "(SUM(CASE WHEN i.tags is empty THEN 1 ELSE 0 END)) AS total_problems_excl_auto")
            ->addSelect(
                "(SUM(CASE WHEN i.title IS NULL THEN 1 ELSE 0 END)) + " .
                "(10 * SUM(CASE WHEN i.latlng IS NULL THEN 1 ELSE 0 END)) + " .
                "(2 * SUM(CASE WHEN i.location IS NULL THEN 1 ELSE 0 END)) + " .
                "(5 * SUM(CASE WHEN i.rating IS NULL OR i.rating = 0 THEN 1 ELSE 0 END)) + " .
                "(0.01 * SUM(CASE WHEN i.tags is empty THEN 1 ELSE 0 END)) + " .
                "(0.001 * SUM(CASE WHEN i.auto_tags IS NULL OR i.auto_tags = 'a:0:{}' THEN 1 ELSE 0 END)) AS weighted_problem_score")
            ->addGroupBy('w')
            ->having('no_title > 0')
            ->orHaving('no_latlng > 0')
            ->orHaving('no_location > 0')
            ->orHaving('no_tags > 0')
            ->orHaving('no_auto_tags > 0')
            ->orderBy('weighted_problem_score', 'desc')
            ->getQuery()
            ->getResult();

        $orphans = $imageRepository->findWithNoWander();

        return $this->render('/admin/problems/index.html.twig', [
            'problems' => $problems,
            'orphans' => $orphans,
            'built_problems' => $builtProblems
        ]);
    }

    /**
     * @Route("/regenerate", name="regenerate", methods={"POST"})
     */
    public function regenerateProblems(Request $request, ProblemService $problemService): Response
    {
        if ($this->isCsrfTokenValid('problems_regenerate', $request->request->get('_token'))) {
            $problemService->createProblemReport();
        }

        return $this->redirectToRoute('admin_problems_index');
    }


    // TODO: These all share a lot of things in common, but I'm not sure
    // how much this is going to get used, so I'm keeping it fairly brain-dead
    // for now. Revisit later.

    /**
     * @Route("/no_title/wander/{id}", name="no_title", methods={"GET"})
     */
    public function noTitle(Wander $wander): Response
    {
        return $this->render('/admin/problems/no_title.html.twig', [
            'wander' => $wander
        ]);
    }
    /**
     * @Route("/no_latlng/wander/{id}", name="no_latlng", methods={"GET"})
     */
    public function noLatlng(Wander $wander): Response
    {
        return $this->render('/admin/problems/no_latlng.html.twig', [
            'wander' => $wander
        ]);
    }
    /**
     * @Route("/no_location/wander/{id}", name="no_location", methods={"GET"})
     */
    public function noLocation(Wander $wander): Response
    {
        return $this->render('/admin/problems/no_location.html.twig', [
            'wander' => $wander
        ]);
    }
    /**
     * @Route("/no_rating/wander/{id}", name="no_rating", methods={"GET"})
     */
    public function noRating(Wander $wander): Response
    {
        return $this->render('/admin/problems/no_rating.html.twig', [
            'wander' => $wander
        ]);
    }

    /**
     * @Route("/no_tags/wander/{id}", name="no_tags", methods={"GET"})
     */
    public function noTags(Wander $wander): Response
    {
        return $this->render('/admin/problems/no_tags.html.twig', [
            'wander' => $wander
        ]);
    }

    /**
     * @Route("/no_auto_tags/wander/{id}", name="no_auto_tags", methods={"GET"})
     */
    public function noAutoTags(Wander $wander): Response
    {
        return $this->render('/admin/problems/no_auto_tags.html.twig', [
            'wander' => $wander
        ]);
    }

    /**
     * @Route("/broken_links", name="broken_links", methods={"GET"})
     */
    public function brokenLinks(ProblemRepository $problemRepository): Response
    {
        $problems = $problemRepository->findAll();
        return $this->render('/admin/problems/broken_links.html.twig', [
            'problems' => $problems
        ]);
    }
}
