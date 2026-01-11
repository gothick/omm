<?php

namespace App\Entity;

use App\EventListener\ImageCalculatedFieldSetterListener;
use App\EventListener\WanderUploadListener;
use App\Repository\ImageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use App\EventListener\SearchIndexer;
use Beelab\TagBundle\Tag\TaggableInterface;
use Beelab\TagBundle\Tag\TagInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Index;
use DateTimeInterface;

/**
 *
 *
 *
 *
 */
#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[ORM\EntityListeners([ImageCalculatedFieldSetterListener::class, SearchIndexer::class])]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Image implements TaggableInterface
{
    /**
     *
     * @var int
     */
    #[Groups(['wander:item', 'image:list'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    // TODO: We probably don't want this massive field being returned
    // as part of any API response, etc.
    /**
     *
     * @var File|null
     */
    #[Ignore]
    #[Vich\UploadableField(mapping: 'image', fileNameProperty: 'name', size: 'sizeInBytes',
        mimeType: 'mimeType', originalName: 'originalName', dimensions: 'dimensions')]
    private $imageFile;

    /**
     * @var string|null
     */
    #[Groups(['wander:item', 'image:list'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $name; // For Vich, not for us. We use Title.
    /**
     * @var string|null
     */
    #[Groups(['wander:item', 'image:list'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $title;

    /**
     * @var string|null
     */
    #[Groups(['wander:item', 'image:list'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var int|null
     */
    #[Groups(['wander:item'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $sizeInBytes;

    /**
     * @var string|null
     */
    #[Groups(['wander:item'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mimeType;

    /**
     * @var string|null
     */
    #[Groups(['wander:item'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $originalName;

    /**
     * @var ?array<int>
     */
    #[Groups(['wander:item'])]
    #[ORM\Column(type: 'simple_array', nullable: true)]
    private $dimensions = [];

    /**
     *
     * @var \DateTimeInterface|null
     */
    #[Groups(['wander:item'])]
    #[ORM\Column(type: 'datetime')]
    private $updatedAt;

    /**
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
     * @var ?array<float>
     */
    #[Groups(['wander:item', 'image:list'])]
    #[ORM\Column(type: 'simple_array', nullable: true)]
    private $latlng = [];

    /**
     * @var Collection<int, TagInterface>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private $tags;

    /**
     * @var DateTimeInterface
     */
    #[Groups(['wander:item'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $capturedAt;

    /**
     * @var ?int
     */
    #[Groups(['wander:item'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $rating;

    // TODO: This @Ignore was here from when this was a many-to-many. Do we still
    // need it?
    /**
     * @var ?Wander
     */
    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Wander::class, inversedBy: 'images')]
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

    #[Ignore]
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

    /**
     * @return ?array<int>
     */
    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    /**
     * @param ?array<int> $dimensions
     *
     */
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

    /**
     * @return ?array<float>
     */
    public function getLatlng(): ?array
    {
        return $this->latlng;
    }

    public function hasLatlng(): bool
    {
        return is_array($this->latlng) && count($this->latlng) == 2;
    }

    /**
     * @param ?array<int> $latlng
     */
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
     * @return Collection<int, TagInterface>
     */
    public function getTags(): iterable
    {
        return $this->tags;
    }

    /**
     * @param Collection<int, TagInterface> $tags
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

    #[ORM\PostLoad]
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

    public function hasCapturedAt(): bool
    {
        return $this->capturedAt !== null;
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
     * @var string
     */
    #[Groups(['wander:item'])]
    private $imageUri;

    /**
     * @var string
     */
    #[Groups(['wander:item', 'image:list'])]
    private $markerImageUri;

    /**
     * @var string
     */
    #[Groups(['wander:item', 'image:list'])]
    private $mediumImageUri;

    /**
     * @var string
     */
    #[Groups(['wander:item', 'image:list'])]
    private $imageShowUri;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $autoTags = [];

    /**
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $location;

    /**
     * @var ?Wander
     */
    #[ORM\OneToOne(targetEntity: Wander::class, inversedBy: 'featuredImage', cascade: ['persist'])]
    private $featuringWander;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $textTags = [];

    public function setImageUri(string $imageUri): void
    {
        $this->imageUri = $imageUri;
    }

    public function getImageUri(): ?string {
        return $this->imageUri;
    }

    public function setMarkerImageUri(string $markerImageUri): void
    {
        $this->markerImageUri = $markerImageUri;
    }
    public function getMarkerImageUri(): ?string {
        return $this->markerImageUri;
    }

    public function setMediumImageUri(string $mediumImageUri): void
    {
        $this->mediumImageUri = $mediumImageUri;
    }
    public function getMediumImageUri(): ?string
    {
        return $this->mediumImageUri;
    }

    public function setImageShowUri(string $imageShowUri): void
    {
        $this->imageShowUri = $imageShowUri;
    }
    public function getImageShowUri(): ?string
    {
        return $this->imageShowUri;
    }

    /**
     * @return array<string>
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
        // TODO: I think we should probably just declare our parameter non-nullable,
        // but I'm not going to try that just yet.
        $this->autoTags = $autoTags === null ? [] : $autoTags;
        return $this;
    }

    public function getAutoTagsCount(): int
    {
        return count($this->autoTags);
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

    /**
     * @return ?array<string>
     */
    public function getTextTags(): ?array
    {
        return $this->textTags;
    }

    public function getTextTagsCount(): int
    {
        return count($this->textTags);
    }


    /**
     * @param array<string> $textTags
     */
    public function setTextTags(array $textTags): self
    {
        $this->textTags = $textTags;
        return $this;
    }
}

