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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\WanderRepository;
use App\Service\GpxService;

/**
 * @Route("/admin/wander")
 */
class WanderController extends AbstractController
{
    /**
     * @Route("/", name="admin_wander_index", methods={"GET"})
     */
    public function index(WanderRepository $wanderRepository): Response
    {
        // TODO: Try pagination
        return $this->render('admin/wander/index.html.twig', [
            'wanders' => $wanderRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="admin_wander_new", methods={"GET","POST"})
     */
    public function new(Request $request, SluggerInterface $slugger, GpxService $gpxService) : Response {
        $wander = new Wander();
        // https://symfony.com/doc/current/controller/upload_file.html
        
        $form = $this->createForm(WanderType::class, $wander, ['type' => 'new']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $gpxFile */
            $gpxFile = $form->get('gpxFilename')->getData();
            
            if ($gpxFile) {
                $originalFilename = pathinfo($gpxFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $gpxFile->guessExtension();
                try {
                    $gpxFile->move(
                        $this->getParameter('gpx_directory'),
                        $newFilename
                    );
                }
                catch (FileException $e) {
                    throw new HttpException(500, "Failed finishing GPX upload: " . $e->getMessage());
                }
                $wander->setGpxFilename($newFilename);
            }
            
            $wander = $form->getData();
            $gpxService->updateWanderStatsFromGpx($wander);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($wander);
            $entityManager->flush();
            return $this->redirectToRoute('admin_wander_show', ['id' => $wander->getId()]);
        }

        return $this->render('admin/wander/new.html.twig', [
            'form' => $form->createView(),
        ]);    
    }

    /**
     * @Route("/{id}", name="admin_wander_show", methods={"GET"})
     */
    public function show(Wander $wander, ImageRepository $imageRepository): Response // Uses “param converter” to find the Wander in db through the {id}
    {
        $images = $imageRepository->findBetweenDates($wander->getStartTime(), $wander->getEndTime());

        return $this->render('admin/wander/show.html.twig', [
            'wander' => $wander
            //'images' => $images
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_wander_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Wander $wander): Response
    {
        $form = $this->createForm(WanderType::class, $wander);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            // It seems to be safe to redirect to show with an ID even after
            // deletion.
            return $this->redirectToRoute('admin_wander_show', ['id' => $wander->getId()]);
        }

        return $this->render('admin/wander/edit.html.twig', [
            'wander' => $wander,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_wander_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Wander $wander): Response
    {
        if ($this->isCsrfTokenValid('delete'.$wander->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($wander);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_wander_index');
    }

}
