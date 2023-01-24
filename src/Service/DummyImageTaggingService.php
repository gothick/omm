<?php

namespace App\Service;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;

class DummyImageTaggingService implements ImageTaggingServiceInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function tagImage(Image $image, bool $overwriteExisting = false): bool
    {
        if ($overwriteExisting === false && $image->getAutoTagsCount() > 0) {
            return false;
        }

        $image->setAutoTags(['dummy', 'image', 'tagging', 'service']);
        $image->setTextTags(['test-dummy', 'test-image', 'test-tagging', 'test-service']);
        $this->entityManager->persist($image);
        $this->entityManager->flush(); // Calling the API's a lot more overhead; we might as well flush on every image.
        return true;
    }
}
