<?php

namespace App\Entity;

use App\Repository\WanderRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WanderRepository::class)
 * 
 * @ApiResource(
 *  collectionOperations={"get"={"normalization_context"={"groups"="wander:list"}}},
 *  itemOperations={"get"={"normalization_context"={"groups"="wander:item"}}},
 *  order={"startTime"="ASC"},
 *  paginationEnabled=false
 * )
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
     * @ORM\Column(type="string", length=255)
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
     * @ORM\Column(type="string", length=1024, nullable=true)
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
}
