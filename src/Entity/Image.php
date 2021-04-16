<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use App\EventListener\ImageCalculatedFieldSetterListener;
use App\EventListener\WanderUploadListener;
use App\Repository\ImageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * @ApiResource(
 *  collectionOperations={"get"={"normalization_context"={"groups"="image:list"}}},
 *  itemOperations={"get"={"normalization_context"={"groups"="image:item"}}},
 *  order={"capturedAt"="ASC"},
 *  paginationEnabled=false
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"wanders": "exact"})
 * @ApiFilter(ExistsFilter::class, properties={"latlng"})
 *
 * @ORM\Entity(repositoryClass=ImageRepository::class)
 *
 * @ORM\EntityListeners({
 *     ImageCalculatedFieldSetterListener::class
 * })
 *
 * @ORM\HasLifecycleCallbacks()
 *
 * This is just to control the stuff that goes back from our one controller
 * action that returns a JSON response, ImageController::upload
 *
 * @Vich\Uploadable
 */

class Image
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @Groups({"image:list", "image:item"})
     */
    private $id;

    // TODO: We probably don't want this massive field being returned
    // as part of any API response, etc.
    /**
     * @Vich\UploadableField(mapping="image", fileNameProperty="name", size="sizeInBytes",
     *  mimeType="mimeType", originalName="originalName", dimensions="dimensions")
     *
     * @Ignore()
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"image:list", "image:item"})
     */
    private $name; // For Vich, not for us. We use Title.

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"image:list", "image:item"})
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"image:list", "image:item"})
     */
    private $description;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Groups({"image:list", "image:item"})
     */
    private $sizeInBytes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"image:list", "image:item"})
     */
    private $mimeType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"image:list", "image:item"})
     */
    private $originalName;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @Groups({"image:list", "image:item"})
     */
    private $dimensions = [];

    /**
     * @ORM\Column(type="datetime")
     *
     * @Groups({"image:list", "image:item"})
     *
     * @var \DateTimeInterface|null
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     * @Assert\Count(
     *      min = 2,
     *      max = 2,
     *      minMessage = "There must be exactly two numbers in a latitude/longitude pair",
     *      maxMessage = "There must be exactly two numbers in a latitude/longitude pair",
     *      exactMessage = "Co-ordinates must consist of a latitude, longitude pair."
     * )
     *
     * @Groups({"image:list", "image:item"})
     *
     */
    private $latlng = [];

    /**
     * @ORM\Column(type="array", nullable=true, )
     *
     * @Groups({"image:list", "image:item"})
     */
    private $keywords = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Groups({"image:list", "image:item"})
     */
    private $capturedAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"image:list", "image:item"})
     */
    private $rating;


    // TODO: This @Ignore was here from when this was a many-to-many. Do we still
    // need it?
    /**
     * @ORM\ManyToOne(targetEntity=Wander::class, inversedBy="images")
     *
     * @Ignore()
     */
    private $wander;

    public function __construct()
    {
        // $this->wanders = new ArrayCollection();
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    /**
     * @Ignore()
     */
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSizeInBytes(): ?int
    {
        return $this->sizeInBytes;
    }

    public function setSizeInBytes(?int $sizeInBytes): self
    {
        $this->sizeInBytes = $sizeInBytes;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function setDimensions(?array $dimensions): self
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLatitude(): ?float
    {
        if ($this->latlng === null ||
            !is_array($this->latlng) ||
            empty($this->latlng)) {
            return null;
        }
        return $this->latlng[0];
    }

    public function getLongitude(): ?float
    {
        if ($this->latlng === null ||
            !is_array($this->latlng) ||
            empty($this->latlng)) {
            return null;
        }
        return $this->latlng[1];
    }

    public function getLatlng(): ?array
    {
        return $this->latlng;
    }

    public function hasLatlng(): bool
    {
        return is_array($this->latlng) && count($this->latlng) == 2;
    }

    public function setLatlng(?array $latlng): self
    {
        $this->latlng = $latlng;

        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getCapturedAt(): ?\DateTimeInterface
    {
        return $this->capturedAt;
    }

    public function setCapturedAt(\DateTimeInterface $capturedAt): self
    {
        $this->capturedAt = $capturedAt;

        return $this;
    }

    // /**
    //  * @return Collection|Wander[]
    //  */
    // public function getWanders(): Collection
    // {
    //     return $this->wanders;
    // }

    // public function addWander(Wander $wander): self
    // {
    //     if (!$this->wanders->contains($wander)) {
    //         $this->wanders[] = $wander;
    //         $wander->addImage($this);
    //     }

    //     return $this;
    // }

    // public function removeWander(Wander $wander): self
    // {
    //     if ($this->wanders->removeElement($wander)) {
    //         $wander->removeImage($this);
    //     }

    //     return $this;
    // }

    public function getWander(): ?Wander
    {
        return $this->wander;
    }

    public function setWander(?Wander $wander): self
    {
        $this->wander = $wander;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }


    /* Computed (set up by Doctrine postLoad listener) */

    /**
     * @Groups({"image:list", "image:item"})
     */
    private $imageUri;

    /**
     * @Groups({"image:list", "image:item"})
     */
    private $markerImageUri;

    /**
     * @Groups({"image:list", "image:item"})
     */
    private $mediumImageUri;

    /**
     * @Groups({"image:list", "image:item"})
     */
    private $imageShowUri;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $auto_tags = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $location;


    public function setImageUri($imageUri) {
        $this->imageUri = $imageUri;
    }

    public function getImageUri(): ?string {
        return $this->imageUri;
    }

    public function setMarkerImageUri($markerImageUri) {
        $this->markerImageUri = $markerImageUri;
    }
    public function getMarkerImageUri(): ?string {
        return $this->markerImageUri;
    }

    public function setMediumImageUri($mediumImageUri) {
        $this->mediumImageUri = $mediumImageUri;
    }
    public function getMediumImageUri(): ?string {
        return $this->mediumImageUri;
    }

    public function setImageShowUri($imageShowUri) {
        $this->imageShowUri = $imageShowUri;
    }
    public function getImageShowUri(): ?string {
        return $this->imageShowUri;
    }

    /**
     * @ORM\PostUpdate
     * @ORM\PostPersist
     */
    public function updateWanderToForceElasticReindex(LifecycleEventArgs $args): self
    {
        // TODO: We may need extra code or a different function
        // if we're to cover the event of an image being deleted,
        // maybe. Test. But for now this is good enough and we can
        // re-index manually if need be.
        $wander = $this->getWander();
        if ($wander === null) {
            return $this;
        }
        $wander->setModifiedAt();
        $entityManager = $args->getEntityManager();
        $entityManager->persist($wander);
        $entityManager->flush();
        return $this;
    }

    public function getAutoTags(): ?array
    {
        return $this->auto_tags;
    }

    public function setAutoTags(?array $auto_tags): self
    {
        $this->auto_tags = $auto_tags;

        return $this;
    }
    public function getAutoTagsCount(): int {
        if (is_array($this->auto_tags)) {
            return count($this->auto_tags);
        }
        return 0;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function hasLocation(): bool
    {
        return $this->location !== null && $this->location <> '';
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }
}

