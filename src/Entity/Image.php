<?php

namespace App\Entity;

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
use App\EventListener\SearchIndexer;
use Beelab\TagBundle\Tag\TaggableInterface;
use Beelab\TagBundle\Tag\TagInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Index;

/**
 *
 * @ORM\Entity(repositoryClass=ImageRepository::class)
 *
 * @ORM\EntityListeners({
 *     ImageCalculatedFieldSetterListener::class,
 *     SearchIndexer::class
 * })
 *
 * @ORM\HasLifecycleCallbacks()
 *
 * This is just to control the stuff that goes back from our one controller
 * action that returns a JSON response, ImageController::upload
 *
 * @Vich\Uploadable
 */

class Image implements TaggableInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @Groups({"wander:item", "image:list"})
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
     * @Groups({"wander:item", "image:list"})
     */
    private $name; // For Vich, not for us. We use Title.

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"wander:item", "image:list"})
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"wander:item", "image:list"})
     */
    private $description;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Groups({"wander:item"})
     */
    private $sizeInBytes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"wander:item"})
     */
    private $mimeType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"wander:item"})
     */
    private $originalName;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @Groups({"wander:item"})
     */
    private $dimensions = [];

    /**
     * @ORM\Column(type="datetime")
     *
     * @Groups({"wander:item"})
     *
     * @var \DateTimeInterface|null
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     * @Assert\AtLeastOneOf({
     *   @Assert\Count(
     *      min = 2,
     *      max = 2,
     *      exactMessage = "Co-ordinates must consist of a latitude, longitude pair (or nothing.)"
     *   ),
     *   @Assert\Count(
     *      min = 0,
     *      max = 0,
     *      exactMessage = "Co-ordinates must consist of a latitude, longitude pair (or nothing.)"
     *   )
     * })
     *
     * @Groups({"wander:item", "image:list"})
     *
     */
    private $latlng = [];

    /**
     * @var Collection
     * @ORM\ManyToMany(targetEntity="Tag")
     */
    private $tags;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Groups({"wander:item"})
     */
    private $capturedAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"wander:item"})
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
        $this->tags = new ArrayCollection();
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

    public function getTitleOrId(): string
    {
        if ($this->title !== null && $this->title != "") {
            return $this->title;
        }
        return (string) "Image " . $this->id;
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

    public function addTag(TagInterface $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function clearTags(): void
    {
        $this->tags->clear();
    }

    public function removeTag(TagInterface $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function hasTag(TagInterface $tag): bool
    {
        return $this->tags->contains($tag);
    }

    /**
     * @return iterable<TagInterface>
     */
    public function getTags(): iterable
    {
        return $this->tags;
    }

    /**
     * @param Collection<TagInterface> $tags
     */
    public function setTags($tags): self
    {
        $this->clearTags();
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }
        return $this;
    }

    /** @var string|null */
    private $tagsText;

    public function setTagsText(?string $tagsText): void
    {
        $this->tagsText = $tagsText;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getTagsText(): ?string
    {
        $this->tagsText = \implode(', ', $this->tags->toArray());
        return $this->tagsText;
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad(): void
    {
        // Bodge to workaround behaviour of BeelabTagBundle, which updates
        // tags on persist, but only from the text tags. So if you don't
        // get/set the tags text, when you persist your entity all its
        // tags disappear. Sigh.
        $this->getTagsText();
    }

    /**
     * @return array<string>
     */
    public function getTagNames(): array
    {
        return empty($this->tagsText) ? [] : \array_map('trim', explode(',', $this->tagsText));
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


    public function getWander(): ?Wander
    {
        return $this->wander;
    }

    public function setWander(?Wander $wander): self
    {
        $this->wander = $wander;
        return $this;
    }

    public function hasWander(): bool
    {
        return ($this->wander !== null);
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
     * @Groups({"wander:item"})
     */
    private $imageUri;

    /**
     * @Groups({"wander:item", "image:list"})
     */
    private $markerImageUri;

    /**
     * @Groups({"wander:item", "image:list"})
     */
    private $mediumImageUri;

    /**
     * @Groups({"wander:item", "image:list"})
     */
    private $imageShowUri;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var ?string[]
     */
    private $autoTags = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @ORM\OneToOne(targetEntity=Wander::class, inversedBy="featuredImage", cascade={"persist"})
     */
    private $featuringWander;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $textTags = [];

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
     * @return ?string[]
     */
    public function getAutoTags(): ?array
    {
        return $this->autoTags;
    }

    /**
     * @param ?string[] $autoTags
     */
    public function setAutoTags(?array $autoTags): self
    {
        $this->autoTags = $autoTags;

        return $this;
    }
    public function getAutoTagsCount(): int
    {
        if (is_array($this->autoTags)) {
            return count($this->autoTags);
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

    public function getFeaturingWander(): ?Wander
    {
        return $this->featuringWander;
    }

    public function setFeaturingWander(?Wander $featuringWander): self
    {
        $this->featuringWander = $featuringWander;

        return $this;
    }

    public function setAsFeaturedImage(): void
    {
        $wander = $this->wander;
        if ($wander === null) {
            throw new \Exception("Can't call setAsFeaturedImage unless the Image is associated with a Wander.");
        }
        $this->setFeaturingWander($wander);
    }

    // Used when building drop-down list of Images to choose as selection on Wander edit screen
    public function __toString(): string
    {
        $result = $this->title ?? (string) $this->id;
        if (isset($this->capturedAt)) {
            $result .= ' (' . $this->capturedAt->format('j M Y') . ')';
        }
        if (isset($this->rating)) {
            $result .= ' ' . str_repeat('★', $this->rating);
        }
        return $result;
    }

    public function getTextTags(): ?array
    {
        return $this->textTags;
    }

    public function getTextTagsCount(): int
    {
        if (is_array($this->textTags)) {
            return count($this->textTags);
        }
        return 0;
    }


    public function setTextTags(?array $textTags): self
    {
        $this->textTags = $textTags;

        return $this;
    }
}

