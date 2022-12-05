<?php

namespace App\Service;

use App\Entity\Image;
use App\Exception\ThirdPartyAPIException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Rpc\Status;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;

use Psr\Log\LoggerInterface;

class GoogleImageTaggingService implements ImageTaggingServiceInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImageAnnotatorClient */
    private $client;

    /** @var string imagesDirectory */

    /** @var LoggerInterface */
    private $logger;

    // These sizes seem to work fine with Google's
    // Vision service, and we don't want to waste
    // bandwidth sending the whole original along.
    private const MAX_WIDTH = 1024;
    private const MAX_HEIGHT = 1024;

    private $imagine;

    public function __construct(
        string $projectId,
        string $serviceAccountFile,
        EntityManagerInterface $entityManager,
        string $imagesDirectory,
        LoggerInterface $logger
    )
    {
        $this->client = new ImageAnnotatorClient([
            'projectId' => $projectId,
            'credentials' => $serviceAccountFile
        ]);
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->imagine = new Imagine();
        $this->imagesDirectory = $imagesDirectory;
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

        // It's possible to get some results back, but still have an error
        // reported. We should probaby pay it some attention...
        /** @var Status|null $error */
        $error = $result->getError();
        if ($error !== null) {
            throw new ThirdPartyAPIException($error->getMessage(), $error->getCode(), $error->getCode());
        }
        return true;
    }

    public function resize(string $filename): string
    {
        list($iwidth, $iheight) = getimagesize($filename);
        $ratio = $iwidth / $iheight;
        $width = self::MAX_WIDTH;
        $height = self::MAX_HEIGHT;
        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        $photo = $this->imagine->open($filename);
        return $photo->resize(new Box($width, $height))->get('jpeg');
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
        // For now I'm going to assume we're using Vich file system storage. If we change
        // that later I'll deal with it then, but this is much cleaner than anything else
        // we could do for now.
        return $this->resize($this->imagesDirectory . '/' . $image->getName());
    }
}
