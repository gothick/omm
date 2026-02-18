<?php

namespace App\Controller\Admin;

use App\Entity\Wander;
use App\Form\WanderType;
use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use App\Repository\WanderRepository;
use App\Service\GpxService;
use App\Service\UploadHelper;
use Doctrine\ORM\Mapping\OrderBy;
use Knp\Component\Pager\PaginatorInterface;

#[Route(path: '/admin/wanders', name: 'admin_wanders_')]
class WanderController extends AbstractController
{
    public function __construct(private readonly \App\Repository\WanderRepository $wanderRepository, private readonly \Knp\Component\Pager\PaginatorInterface $paginator, private readonly \App\Service\GpxService $gpxService, private readonly \App\Service\UploadHelper $uploadHelper, private readonly \Doctrine\Persistence\ManagerRegistry $doctrine)
    {
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(
        Request $request
        ): Response
    {
        $filterHasImages = null;

        // Customise the query to add an imageCount built-in so we can efficiently
        // (and at all :) ) sort it in our paginator.
        $qb = $this->wanderRepository->wandersWithImageCountQueryBuilder();

        if ($request->query->has('hasImages')) {
            $filterHasImages = $request->query->getBoolean('hasImages');
            $this->wanderRepository->addWhereHasImages($qb, $filterHasImages);
        }

        $query = $qb->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/wander/index.html.twig', [
            'pagination' => $pagination,
            'filter_has_images' => $filterHasImages
        ]);
    }

    #[Route(path: '/backlog.{!_format}', name: 'backlog', requirements: ['_format' => 'html|txt'], methods: ['GET'], format: 'html')]
    public function backlog(
        Request $request
    ): Response
    {
        $qb = $this->wanderRepository
            ->standardQueryBuilder()
            ->OrderBy('w.startTime');
        $this->wanderRepository->addWhereHasImages($qb, false);
        $wanders = $qb->getQuery()->getResult();
        $response = new Response();
        $format = $request->getRequestFormat();
        return $this->render(
            'admin/wander/backlog.'.$format.'.twig',
            [
                'wanders' => $wanders
            ],
            $response
        );
    }

    #[Route(path: '/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
            Request $request
        ): Response
    {
        $wander = new Wander();
        // https://symfony.com/doc/current/controller/upload_file.html

        $form = $this->createForm(WanderType::class, $wander, ['type' => 'new']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $gpxFile */
            $gpxFile = $form->get('gpxFilename')->getData();

            if ($gpxFile) {
                $wander->setGpxFilename($this->uploadHelper->uploadGpxFile($gpxFile));
            }

            $wander = $form->getData();
            $this->gpxService->updateWanderFromGpx($wander);

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($wander);
            $entityManager->flush();
            return $this->redirectToRoute('admin_wanders_show', ['id' => $wander->getId()]);
        }

        return $this->render('admin/wander/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}', name: 'show', methods: ['GET'])]
    public function show(Wander $wander): Response // Uses “param converter” to find the Wander in db through the {id}
    {
        return $this->render('admin/wander/show.html.twig', [
            'wander' => $wander
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Wander $wander): Response
    {
        $form = $this->createForm(WanderType::class, $wander);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            // It seems to be safe to redirect to show with an ID even after
            // deletion.
            return $this->redirectToRoute('admin_wanders_show', ['id' => $wander->getId()]);
        }

        return $this->render('admin/wander/edit.html.twig', [
            'wander' => $wander,
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Wander $wander): Response
    {
        if ($this->isCsrfTokenValid('delete'.$wander->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($wander);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_wanders_index');
    }

    #[Route(path: '/{id}/delete_images', name: 'delete_images', methods: ['POST'])]
    public function deleteImages(Request $request, Wander $wander): Response
    {
        if ($this->isCsrfTokenValid('delete_images'.$wander->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $images = $wander->getImages();
            foreach ($images as $image) {
                $entityManager->remove($image);
            }

            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_wanders_show', ['id' => $wander->getId()]);
    }
}
