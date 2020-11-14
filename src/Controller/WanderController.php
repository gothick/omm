<?php

namespace App\Controller;

use App\Entity\Wander;
use App\Form\WanderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\WanderRepository;
use phpGPX\phpGPX;

/**
 * @Route("/wander")
 */
class WanderController extends AbstractController
{
    /**
     * @Route("/", name="wander_index", methods={"GET"})
     */
    public function index(WanderRepository $wanderRepository): Response
    {
        // TODO: Try pagination
        return $this->render('wander/index.html.twig', [
            'wanders' => $wanderRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="wander_new", methods={"GET","POST"})
     */
    public function new(Request $request, SluggerInterface $slugger) : Response {
        $wander = new Wander();
        // TODO When creating an edit form, bear in mind:
        /*
        https://symfony.com/doc/current/controller/upload_file.html
        When creating a form to edit an already persisted item, the file form type still expects a Symfony\Component\HttpFoundation\File\File instance. As the persisted entity now contains only the relative file path, you first have to concatenate the configured upload path with the stored filename and create a new File class:
        */

 
        // TODO: You can move this into a separate form class. See https://symfony.com/doc/current/forms.html
        // TODO: How much can we consolidate this with App/Form/WanderType?
        /*
        $form = $this->createFormBuilder($wander)
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)
            // Read directly from the tracks in the GPX file for new uploads
            //->add('startTime', DateTimeType::class)
            //->add('endTime', DateTimeType::class)
            ->add('gpxFilename', FileType::class, [
                'label' => 'GPX track file',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            "application/gpx+xml","text/xml","application/xml","application/octet-stream"
                        ],
                        'mimeTypesMessage' =>'Please upload a valid GPX document'
                    ])
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Save'])
            ->getForm();
        */

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

            // Set up some Wander values by reading the GPX file directly
            $phpGPX = new phpGPX();
            $file = $phpGPX->load($this->getParameter('gpx_directory') . '/' . $newFilename);
            // TODO: Cope with multiple traks
            foreach ($file->tracks as $track) {
                $wander->setStartTime($track->stats->startedAt);
                $wander->setEndTime($track->stats->finishedAt);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($wander);
            $entityManager->flush();
            return $this->redirectToRoute('wander_show', ['id' => $wander->getId()]);
        }

        return $this->render('wander/new.html.twig', [
            'form' => $form->createView(),
        ]);    
    }

    /**
     * @Route("/{id}", name="wander_show", methods={"GET"})
     */
    public function show(Wander $wander): Response // Uses “param converter” to find the Wander in db through the {id}
    {
        return $this->render('wander/show.html.twig', [
            'wander' => $wander,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="wander_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Wander $wander): Response
    {
        $form = $this->createForm(WanderType::class, $wander);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('wander_index');
        }

        return $this->render('wander/edit.html.twig', [
            'wander' => $wander,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="wander_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Wander $wander): Response
    {
        if ($this->isCsrfTokenValid('delete'.$wander->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($wander);
            $entityManager->flush();
        }

        return $this->redirectToRoute('wander_index');
    }

}
