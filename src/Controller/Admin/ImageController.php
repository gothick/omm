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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @Route("/admin/images", name="admin_images_")
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(ImageRepository $imageRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $imageRepository
            ->createQueryBuilder('i')
            // Nice orphan check: ->where('i.wanders is empty')
            ->orderBy('i.capturedAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10);

        return $this->render('admin/image/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/cluster", name="cluster", methods={"GET"})
     */
    public function cluster()
    {
        return $this->render('admin/image/cluster.html.twig', [
        ]);
    }

    /**
     * @Route("/upload", name="upload", methods={"GET", "POST"})
     */
    public function upload(
        Request $request,
        SerializerInterface $serializer,
        string $gpxDirectory // TODO Fix this; it should be using the image uploads directory
        ): Response
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
                    // It's not exactly an API response, but it'll do until we switch to handling this
                    // a bit more properly. At least it's a JSON repsonse and *doesn't include the entire
                    // file we just uploaded*, thanks to the IGNORED_ATTRIBUTES. Because we set up the
                    // image URIs in a postPersist event listener, this also contains everything you'd
                    // need to build an image in HTML.
                    return new JsonResponse($serializer->serialize($image, 'jsonld', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['imageFile']]), 201,[], true);
                }
            }
        }
        else
        {
            $disk = [];
            $disk['free'] = disk_free_space($gpxDirectory);
            $disk['total'] = disk_total_space($gpxDirectory);
            $disk['used'] = $disk['total'] - $disk['free'];
            $disk['percent'] = $disk['used'] / $disk['total'];
            return $this->render('admin/image/upload.html.twig', [
                 'disk' => $disk
            ]);
        }
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(Image $image): Response
    {
        return $this->render('/admin/image/show.html.twig', [
            'image' => $image,
        ]);
    }

    // TODO Remove responsiveTest once you're done playing
    /**
     * @Route("/responsive_test/{id}", name="responsive_test", methods={"GET"})
     */
    public function responsiveTest(Image $image, CacheManager $imagineCacheManager, FilterManager $filterManager, UploaderHelper $uploaderHelper): Response
    {
        return $this->render('admin/image/responsive_test.html.twig', [
            'image' => $image
        ]);
    }


    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Image $image): Response
    {
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            //dd($form);
            return $this->redirectToRoute('admin_images_show', ['id' => $image->getId()]);
        }

        return $this->render('admin/image/edit.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"DELETE"})
     */
    public function delete(Request $request, Image $image): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_images_index');
    }
}
