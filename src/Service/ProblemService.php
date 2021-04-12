<?php

namespace App\Service;

use App\Entity\Problem;
use App\Repository\ImageRepository;
use App\Repository\ProblemRepository;
use App\Repository\WanderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    /** @var MarkdownService */
    private $markdownService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        problemRepository $problemRepository,
        WanderRepository $wanderRepository,
        ImageRepository $imageRepository,
        RouterInterface $router,
        MarkdownService $markdownService,
        EntityManagerInterface $entityManager
        )
    {
        $this->problemRepository = $problemRepository;
        $this->wanderRepository = $wanderRepository;
        $this->imageRepository = $imageRepository;
        $this->router = $router;
        $this->markdownService = $markdownService;
        $this->entityManager = $entityManager;
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
            $uri = $this->router->generate('wanders_show', [ 'id' => $wander->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $validUris[$uri] = true; // Really I just want a hash that doesn't actually map
        }

        $images = $this->imageRepository->findAll();
        foreach ($images as $image) {
            $uri = $this->router->generate('image_show', [ 'id' => $image->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $validUris[$uri] = true; // Really I just want a hash that doesn't actually map
        }
        $homepage = $this->router->generate('home', [], RouterInterface::ABSOLUTE_URL);

        // Okay, now we've got a list of all valid URIs, let's have a look through all our descriptions

        // Wanders first...
        foreach($wanders as $wander) {
            $links  = $this->markdownService->findLinks($wander->getDescription());
            foreach ($links as $link) {
                if (substr($link['uri'], 0, strlen($homepage)) == $homepage) {
                    if (!array_key_exists($link['uri'], $validUris)) {
                        $problem = new Problem();
                        $problem->setDescription('Wander ' . $wander->getId() . ' links to invalid URI: ' . $link['uri'] . ' (text is: "' . $link['text'] . '")');
                        $problem->setUri($this->router->generate('wanders_show', [ 'id' => $wander->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
                        $this->entityManager->persist($problem);
                    }
                }
            }
        }
        $this->entityManager->flush();
        // ...then Images:
        foreach($images as $image) {
            $links = $this->markdownService->findLinks($image->getDescription());
            foreach ($links as $link) {
                if (substr($link['uri'], 0, strlen($homepage)) == $homepage) {
                    if (!array_key_exists($link['uri'], $validUris)) {
                        $problem = new Problem();
                        $problem->setDescription('Image ' . $image->getId() . ' links to invalid URI: ' . $link['uri'] . ' (text is: "' . $link['text'] . '")');
                        $problem->setUri($this->router->generate('image_show', [ 'id' => $image->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
                        $this->entityManager->persist($problem);
                    }
                }
            }
        }
        $this->entityManager->flush();
    }
}