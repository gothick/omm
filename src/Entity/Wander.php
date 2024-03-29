<?php

namespace App\Entity;

use App\Repository\WanderRepository;
use DateInterval;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Index;


/**
 * @ORM\Entity(repositoryClass=WanderRepository::class)
 *
 * @Table(indexes={@Index(name="ix_wander_start_time", columns={"start_time"})})
 *
 * @ORM\HasLifecycleCallbacks()
 *
 */
class Wander
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @Groups({"wander:list", "wander:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1024)
     *
     * @Groups({"wander:list", "wander:item"})
     */
    private $title;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Groups({"wander:list", "wander:item"})
     */
    private $startTime;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Groups({"wander:list", "wander:item"})
     */
    private $endTime;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"wander:list", "wander:item"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups({"wander:list", "wander:item"})
     */
    private $gpxFilename;

    /**
     * @ORM\OneToMany(targetEntity=Image::class, mappedBy="wander", cascade={"persist"})
     * @ORM\OrderBy({ "capturedAt" = "ASC", "id" = "ASC" })
     *
     * @Groups({"wander:item"})
     */
    private $images;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * Distance walked, in metres.
     *
     */
    private $distance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $avgSpeed;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $avgPace;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $minAltitude;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $maxAltitude;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cumulativeElevationGain;

    /**
     * @var string|null
     *
     * @Groups({"wander:list", "wander:item"})
     */
    public $contentUrl;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $centroid = [];

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $angleFromHome;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

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

    public function getGpxFilename(): ?string
    {
        return $this->gpxFilename;
    }

    public function setGpxFilename(string $gpxFilename): self
    {
        $this->gpxFilename = $gpxFilename;

        return $this;
    }

    public function isTimeLengthSuspicious()
    {
        $interval = $this->startTime->diff($this->endTime, true);
        return $interval->h > 12;
    }

    /**
     * @return Collection|Image[]
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    /**
     * @return Collection|Image[]
     */
    public function getImagesWithNoTitle(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('title'));
        return $this->getImages()->matching($criteria);
    }

    /**
     * @return Collection|Image[]
     */
    public function getImagesWithNoLatLng(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('latlng'));
        return $this->getImages()->matching($criteria);
    }

    /**
     * @return Collection|Image[]
     */
    public function getImagesWithNoLocation(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('location'));
        return $this->getImages()->matching($criteria);
    }

    /**
     * @return Collection|Image[]
     */
    public function getImagesWithNoRating(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('rating'));
        return $this->getImages()->matching($criteria);
    }

    /**
     * @return Collection|Image[]
     */
    public function getImagesWithNoTags(): Collection
    {
        return $this->getImages()->filter(function($image) {
            return $image->getTags()->isEmpty();
        });
    }

    /**
     * @return Collection|Image[]
     */
    public function getImagesWithNoAutoTags(): Collection
    {
        return $this->getImages()->filter(function($image) {
            return $image->getAutoTagsCount() == 0;
        });
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setWander($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        $image->setWander(null);
        $this->images->removeElement($image);

        return $this;
    }

    /**
     * @ORM\PreRemove
     */
    public function removeAllImages(): self
    {
        $images = $this->images;
        foreach ($images as $image) {
            $this->removeImage($image);
        }
        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getAvgSpeed(): ?float
    {
        return $this->avgSpeed;
    }

    public function setAvgSpeed(?float $avgSpeed): self
    {
        $this->avgSpeed = $avgSpeed;

        return $this;
    }

    public function getAvgPace(): ?float
    {
        return $this->avgPace;
    }

    public function setAvgPace(?float $avgPace): self
    {
        $this->avgPace = $avgPace;

        return $this;
    }

    public function getMinAltitude(): ?float
    {
        return $this->minAltitude;
    }

    public function setMinAltitude(?float $minAltitude): self
    {
        $this->minAltitude = $minAltitude;

        return $this;
    }

    public function getMaxAltitude(): ?float
    {
        return $this->maxAltitude;
    }

    public function setMaxAltitude(?float $maxAltitude): self
    {
        $this->maxAltitude = $maxAltitude;

        return $this;
    }

    public function getCumulativeElevationGain(): ?float
    {
        return $this->cumulativeElevationGain;
    }

    public function setCumulativeElevationGain(?float $cumulativeElevationGain): self
    {
        $this->cumulativeElevationGain = $cumulativeElevationGain;

        return $this;
    }

    public function getDuration(): ?CarbonInterval
    {
        if (!isset($this->startTime, $this->endTime)) {
            return null;
        }

        $difference = CarbonInterval::instance($this->startTime->diff($this->endTime));
        return $difference;
    }

    /**
     * @return array<float>
     */
    public function getCentroid(): array
    {
        return $this->centroid;
    }

    /**
     * @param array<float> $centroid
     */
    public function setCentroid(?array $centroid): self
    {
        $this->centroid = $centroid;

        return $this;
    }

    // We could calculate the angle from the Centroid each time, but
    // for now I'm just going to store it to save time.
    public function getAngleFromHome(): ?float
    {
        return $this->angleFromHome;
    }

    public function setAngleFromHome(?float $angleFromHome): self
    {
        $this->angleFromHome = $angleFromHome;

        return $this;
    }

    // Utilities

    // TODO: This is more like display code. Perhaps this should be in
    // a Twig extension and we should stick to storing the angle in here.

    /**
     * @var array<int, string>
     */
    private static $compass = [
        0 => 'N',
        1 => 'NE',
        2 => 'NE',
        3 => 'E',
        4 => 'E',
        5 => 'SE',
        6 => 'SE',
        7 => 'S',
        8 => 'S',
        9 => 'SW',
        10 => 'SW',
        11 => 'W',
        12 => 'W',
        13 => 'NW',
        14 => 'NW',
        15 => 'N'
    ];

    /**
     * @ORM\OneToOne(targetEntity=Image::class, mappedBy="featuringWander", cascade={"persist"})
     * @Groups({"wander:item"})
     */
    private $featuredImage;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"wander:list", "wander:item"})
     *
     */
    private $googlePolyline;

    public function getSector(): ?string
    {
        if ($this->angleFromHome !== null) {
            if ($this->angleFromHome >= 0 && $this->angleFromHome < 360.0) {
                return self::$compass[floor($this->angleFromHome / 22.5)];
            }
        }
        return null;
    }

    public function getCentroidAsString(): ?string {
        if ($this->centroid !== null) {
            return implode(", ", $this->centroid);
        }
        return null;
    }

    // TODO: Put this in but didn't use it in the end; maybe I don't need it
    // as in Twig we can use wander.images.count anyway. Take out?
    public function getImageCount(): int
    {
        // https://stackoverflow.com/a/8511611/300836
        return $this->images->count();
    }

    public function __toString():string
    {
        $result = $this->title ?? '';
        if (isset($this->startTime)) {
            $result .= ' (' . $this->startTime->format('j M Y') . ')';
        }
        return $result;
    }

    public function getFeaturedImage(): ?Image
    {
        return $this->featuredImage;
    }

    public function hasFeaturedImage(): bool
    {
        return $this->featuredImage !== null;
    }

    public function setFeaturedImage(?Image $featuredImage): self
    {
        // unset the owning side of the relation if necessary
        if ($this->featuredImage !== null) {
            $this->featuredImage->setFeaturingWander(null);
        }

        // set the owning side of the relation if necessary
        if ($featuredImage !== null && $featuredImage->getFeaturingWander() !== $this) {
            $featuredImage->setFeaturingWander($this);
        }

        $this->featuredImage = $featuredImage;

        return $this;
    }

    public function getGooglePolyline(): ?string
    {
        return $this->googlePolyline;
    }

    public function setGooglePolyline(?string $googlePolyline): self
    {
        $this->googlePolyline = $googlePolyline;

        return $this;
    }
}
