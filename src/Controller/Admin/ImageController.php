<?php

namespace App\Controller\Admin;

use App\Entity\Image;
use App\Form\ImageType;
use App\Form\DropzoneImageType;
use App\Repository\ImageRepository;
use Knp\Component\Pager\PaginatorInterface;
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
 * @Route("/admin/image")
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/", name="admin_image_index", methods={"GET"})
     */
    public function index(ImageRepository $imageRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $imageRepository
            ->createQueryBuilder('i') 
            ->orderBy('i.capturedAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query, 
            $request->query->getInt('page', 1),
            5);

        return $this->render('admin/image/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/cluster", name="admin_image_cluster", methods={"GET"})
     */
    public function cluster(ImageRepository $imageRepository)
    {
        return $this->render('admin/image/cluster.html.twig', [
        ]);        
    }

    /**
     * @Route("/upload", name="admin_image_upload", methods={"GET", "POST"})
     */
    public function upload(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $token = $request->request->get('token');
            if (!$this->isCsrfTokenValid('image_upload', $token)) {
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
            return $this->render('admin/image/upload.html.twig', []);
        }
    }

    /**
     * @Route("/{id}", name="admin_image_show", methods={"GET"})
     */
    public function show(Image $image): Response
    {
        return $this->render('/admin/image/show.html.twig', [
            'image' => $image,
        ]);
    }

    // TODO Remove responsiveTest once you're done playing
    /**
     * @Route("/responsive_test/{id}", name="admin_image_responsive_test", methods={"GET"})
     */
    public function responsiveTest(Image $image, CacheManager $imagineCacheManager, FilterManager $filterManager, UploaderHelper $uploaderHelper): Response
    {
        return $this->render('admin/image/responsive_test.html.twig', [
            'image' => $image
        ]);
    }


    /**
     * @Route("/{id}/edit", name="admin_image_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Image $image): Response
    {
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_image_show', ['id' => $image->getId()]);
        }

        return $this->render('admin/image/edit.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_image_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Image $image): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_image_index');
    }
}
