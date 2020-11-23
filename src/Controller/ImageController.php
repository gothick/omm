<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageType;
use App\Form\DropzoneImageType;
use App\Repository\ImageRepository;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @Route("/image")
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/", name="image_index", methods={"GET"})
     */
    public function index(ImageRepository $imageRepository): Response
    {
        return $this->render('image/index.html.twig', [
            'images' => $imageRepository->findBy(array(), array('updatedAt' => 'DESC'))
        ]);
    }

    /**
     * @Route("/dropzone", name="image_dropzone_test", methods={"GET", "POST"})
     */
    public function dropzone(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $token = $request->request->get('token');
            if (!$this->isCsrfTokenValid('image_dropzone_test', $token)) {
                return $this->json([ 'error' => 'Invalid CSRF token'], 401);
            }
            $files = $request->files->all();
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $image = new Image();
                    $image->setImageFile($file);
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($image);
                    $entityManager->flush();
                    return $this->json($image);
                }
            }
        }
        else
        {
            return $this->render('image/dropzone.html.twig', []);
        }
    }

    /**
     * @Route("/new", name="image_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($image);
            $entityManager->flush();

            return $this->redirectToRoute('image_index');
        }

        return $this->render('image/new.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="image_show", methods={"GET"})
     */
    public function show(Image $image): Response
    {
        return $this->render('image/show.html.twig', [
            'image' => $image,
        ]);
    }

    /**
     * @Route("/responsive_test/{id}", name="image_responsive_test", methods={"GET"})
     */
    public function responsiveTest(Image $image, CacheManager $imagineCacheManager, FilterManager $filterManager, UploaderHelper $uploaderHelper): Response
    {
        $image_asset_path = $uploaderHelper->asset($image);
        $filters = $filterManager->getFilterConfiguration()->all();
        $srcset = [];
        array_walk(
            $filters,
            function($val, $key) use(&$srcset, $image_asset_path, $imagineCacheManager) {
                
                if (preg_match('/^srcset/', $key)) {
                    $width = $val['filters']['relative_resize']['widen'];
                    $path = $imagineCacheManager->getBrowserPath($image_asset_path, $key);
                    $srcset[] = ['width' => $width, 'path' => $path];
                }
            });

        // Add original image as srcset option, otherwise it may never be used.
        $srcset[] = ['width' => $image->getDimensions()[0], 'path' => $image_asset_path];

        $srcsetString = implode(', ', array_map(function ($src) {
            return sprintf(
                '%s %uw',
                $src['path'],
                $src['width']
            );
        }, $srcset));

        return $this->render('image/responsive_test.html.twig', [
            'image' => $image, 
            'srcset' => $srcsetString
        ]);
    }


    /**
     * @Route("/{id}/edit", name="image_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Image $image): Response
    {
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('image_index');
        }

        return $this->render('image/edit.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="image_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Image $image): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('image_index');
    }
}
