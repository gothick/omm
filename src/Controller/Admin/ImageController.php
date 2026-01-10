<?php

namespace App\Controller\Admin;

use App\Entity\Image;
use App\Form\ImageType;
use App\Message\RecogniseImage;
use App\Message\WarmImageCache;
use App\Repository\ImageRepository;
use App\Service\DiskStatsService;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route(path: '/admin/image', name: 'admin_image_')]
class ImageController extends AbstractController
{
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(ImageRepository $imageRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $imageRepository
            ->createQueryBuilder('i')
            // Nice orphan check: ->where('i.wanders is empty')
            ->orderBy('i.capturedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10);

        return $this->render('admin/image/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route(path: '/cluster', name: 'cluster', methods: ['GET'])]
    public function cluster(): Response
    {
        return $this->render('admin/image/cluster.html.twig', [
        ]);
    }

    #[Route(path: '/upload', name: 'upload', methods: ['GET', 'POST'])]
    public function upload(
            Request $request,
            string $imagesDirectory,
            DiskStatsService $diskStatsService,
            ManagerRegistry $managerRegistry
    ): Response {
        if ($request->isMethod('POST')) {

            $token = (string) $request->request->get('token');
            if (!$this->isCsrfTokenValid('image_upload', $token)) {
                return $this->json([ 'error' => 'Invalid CSRF token'], 401);
            }

            $file = $request->files->get('file');
            if (!$file instanceof UploadedFile) {
                throw new HttpException(500, "No uploaded file found.");
            }

            $image = new Image();
            $image->setImageFile($file);
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($image);
            $entityManager->flush();

            // It's not exactly an API response, but it'll do until we switch to handling this
            // a bit more properly. At least it's a JSON repsonse and *doesn't include the entire
            // file we just uploaded*, thanks to the wander:item grouping limiting the returned
            // fields. Because we set up the image URIs in a postPersist event listener, this
            //  also contains everything you'd need to build an image in HTML.
            return $this->json(
                $image,
                Response::HTTP_OK,
                [],
                [
                    'groups' => 'wander:item',
                ]
            );
        }

        // Normal GET request.
        $disk = $diskStatsService->getDiskStats($imagesDirectory);

        return $this->render('admin/image/upload.html.twig', [
                'disk' => $disk
        ]);
    }

    #[Route(path: '/{id}', name: 'show', methods: ['GET'])]
    public function show(Image $image): Response
    {
        return $this->render('admin/image/show.html.twig', [
            'image' => $image,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Image $image,
        ManagerRegistry $managerRegistry
    ): Response {
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();
            return $this->redirectToRoute('admin_image_show', ['id' => $image->getId()]);
        }

        return $this->render('admin/image/edit.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        Request $request,
        Image $image,
        ManagerRegistry $managerRegistry
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), (string) $request->request->get('_token'))) {
            $entityManager = $managerRegistry->getManager();
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_image_index');
    }

    #[Route(path: '/{id}/set_location', name: 'set_location', methods: ['POST'])]
    public function setLocation(Request $request, Image $image, LocationService $locationService, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('set_location'.$image->getId(), (string) $request->request->get('_token'))) {
            $neighbourhood  = $locationService->getLocationName($image->getLatitude(), $image->getLongitude());
            if ($neighbourhood !== null) {
                $image->setLocation($neighbourhood);
                $entityManager->persist($image);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('admin_image_show', ['id' => $image->getId()]);
    }

    #[Route(path: '/{id}/set_auto_tags', name: 'set_auto_tags', methods: ['POST'])]
    public function setAutoTags(Request $request, Image $image, MessageBusInterface $messageBus): Response
    {
        if ($this->isCsrfTokenValid('set_auto_tags'.$image->getId(), (string) $request->request->get('_token'))) {
            $imageId = $image->getId();
            if ($imageId === null) {
                throw new InvalidParameterException('No image id in setAutoTags');
            }
            $messageBus->dispatch(new RecogniseImage($imageId, true));
            $this->addFlash('success', 'Image re-queued for recognition.');
        }
        return $this->redirectToRoute('admin_image_show', ['id' => $image->getId()]);
    }
}
