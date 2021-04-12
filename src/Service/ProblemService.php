<?php

namespace App\Service;

use App\Repository\ImageRepository;
use App\Repository\ProblemRepository;
use App\Repository\WanderRepository;
use Symfony\Component\Routing\RouterInterface;

class ProblemService
{
    /** @var ProblemRepository */
    private $problemRepository;

    /** @var WanderRepository */
    private $wanderRepository;

    /** @var ImageRepository */
    private $imageRepository;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        problemRepository $problemRepository,
        WanderRepository $wanderRepository,
        ImageRepository $imageRepository,
        RouterInterface $router
        )
    {
        $this->problemRepository = $problemRepository;
        $this->wanderRepository = $wanderRepository;
        $this->imageRepository = $imageRepository;
        $this->router = $router;
    }

    public function createProblemReport(): void
    {
        // I don't care about concurrency; there's only me using this and
        // I can always run it again if there's a problem; it's an unimportant
        // operation.
        $this->problemRepository->clearAllProblems();

        // Build a hash of all possible valid URLs for wanders and
        // images to compare what we find in descriptions, etc. with.
        $validUris = [];
        $wanders = $this->wanderRepository->findAll();
        foreach ($wanders as $wander) {
            $uri = $this->router->generate('wanders_show', [ 'id' => $wander->getId()]);
            $validUris[$uri] = true; // Really I just want a hash that doesn't actually map
        }

        $images = $this->imageRepository->findAll();
        foreach ($images as $image) {
            $uri = $this->router->generate('image_show', [ 'id' => $image->getId()]);
            $validUris[$uri] = true; // Really I just want a hash that doesn't actually map
        }
        dd($validUris);
    }
}