<?php

namespace App\Service;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

class GoogleImageTaggingService implements ImageTaggingServiceInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImageAnnotatorClient */
    private $client;

    public function __construct(
        string $projectId,
        string $serviceAccountFile,
        EntityManagerInterface $entityManager
    )
    {
        //dd(file_get_contents($serviceAccountFile));
        $this->client = new ImageAnnotatorClient([
            'projectId' => $projectId,
            'credentials' => $serviceAccountFile
        ]);
        $this->entityManager = $entityManager;
    }

    public function tagImage(Image $image, bool $overwriteExisting = false): bool
    {
        if ($overwriteExisting === false && $image->getAutoTagsCount() > 0) {
            return false;
        }

        $feature = new Feature();
        $feature->setType(Type::LABEL_DETECTION);
        $feature->setMaxResults(15);
        $result = $this->client->annotateImage(
            $this->getImageToSend($image),
            [$feature]
        );

        $tags = [];
        foreach ($result->getLabelAnnotations() as $annotation) {
            $tags[] = $annotation->getDescription();
        }
        $image->setAutoTags($tags);
        $this->entityManager->persist($image);
        $this->entityManager->flush(); // Calling the API's a lot more overhead; we might as well flush on every image.
        return true;
    }

    /**
     *
     * Can be overridden, e.g. our dev service overrides this to provide
     * the actual image data through readfile as the dev service's URLs
     * aren't public.
     *
     * @return mixed|string
     */
    protected function getImageToSend(Image $image)
    {
        // We just send back a reasonable-resolution public URL
        return $image->getMediumImageUri();
    }
}