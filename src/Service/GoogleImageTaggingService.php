<?php

namespace App\Service;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Psr\Log\LoggerInterface;

class GoogleImageTaggingService implements ImageTaggingServiceInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImageAnnotatorClient */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        string $projectId,
        string $serviceAccountFile,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    )
    {
        $this->client = new ImageAnnotatorClient([
            'projectId' => $projectId,
            'credentials' => $serviceAccountFile
        ]);
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function tagImage(Image $image, bool $overwriteExisting = false): bool
    {
        if ($overwriteExisting === false && ($image->getAutoTagsCount() > 0 || $image->getTextTagsCount() > 0)) {
            return false;
        }

        $labelDetection = new Feature();
        $labelDetection->setType(Type::LABEL_DETECTION);
        $labelDetection->setMaxResults(15);

        $textDetection = new Feature();
        $textDetection->setType(Type::TEXT_DETECTION);
        $textDetection->setMaxResults(100);

        $result = $this->client->annotateImage(
            $this->getImageToSend($image),
            [$labelDetection, $textDetection]
        );

        $tags = [];
        foreach ($result->getLabelAnnotations() as $annotation) {
            $tags[] = $annotation->getDescription();
        }
        $image->setAutoTags($tags);

        $tags = [];
        foreach ($result->getTextAnnotations() as $annotation) {
            $tags[] = $annotation->getDescription();
        }
        $image->setTextTags($tags);

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