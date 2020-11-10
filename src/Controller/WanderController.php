<?php

namespace App\Controller;

use App\Entity\Wander;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;

class WanderController extends AbstractController
{
    /**
     * @Route("/wanders", name="wander")
     */
    public function index(): Response
    {
        return $this->render('wander/index.html.twig', [
            'controller_name' => 'WanderController',
        ]);
    }

    /**
     * @Route("/wanders/testadd", name="testadd_wander")
     */
    public function createWander(): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $wander = new Wander();
        $wander->setTitle("I'm a wander.");
        $wander->setStartTime(new \DateTime());
        $wander->setEndTime(new \DateTime());
        $wander->setDescription("I'm a soulful description of a wander in Hotwells");
        $entityManager->persist($wander);
        $entityManager->flush();
        return new Response("Saved a new wander with id " . $wander->getId());
    }

    /**
     * @Route("/wanders/show/{id}", name="wander_show")
     */
    public function showWander(int $id): Response
    {
        $wander = $this->getDoctrine()->getRepository(Wander::class)->find($id);
        if (!$wander) {
            throw $this->createNotFoundException('No wander found for id' . $id);
        }
        return $this->render('wander/show.html.twig', [
            'wander' => $wander
        ]);
    }

    /**
     * @Route("/wanders/new", name="new_wander")
     */
    public function new(Request $request, SluggerInterface $slugger) : Response {
        $wander = new Wander();
        /*
        $wander->setTitle("I'm a wander.");
        $wander->setStartTime(new \DateTime());
        $wander->setEndTime(new \DateTime());
        $wander->setDescription("I'm a soulful description of a wander in Hotwells");
        */

        // TODO When creating an edit form, bear in mind:
        /*
        https://symfony.com/doc/current/controller/upload_file.html
        When creating a form to edit an already persisted item, the file form type still expects a Symfony\Component\HttpFoundation\File\File instance. As the persisted entity now contains only the relative file path, you first have to concatenate the configured upload path with the stored filename and create a new File class:
        */


        // TODO You can move this into a separate form class. See https://symfony.com/doc/current/forms.html
        $form = $this->createFormBuilder($wander)
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)
            ->add('startTime', DateTimeType::class)
            ->add('endTime', DateTimeType::class)
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
            ->add('save', SubmitType::class, ['label' => 'Create Wander'])
            ->getForm();

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
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($wander);
            $entityManager->flush();
            return $this->redirectToRoute('wander_show', ['id' => $wander->getId()]);
        }

        return $this->render('wander/new.html.twig', [
            'form' => $form->createView(),
        ]);    
    }
}
