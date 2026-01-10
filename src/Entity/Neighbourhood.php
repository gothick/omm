<?php

namespace App\Entity;

use App\Repository\NeighbourhoodRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NeighbourhoodRepository::class)]
class Neighbourhood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'geometry')]
    private $boundingPolygon;

    #[ORM\Column(type: 'integer')]
    private $objectId;

    #[ORM\Column(type: 'integer')]
    private $miPrinx;

    #[ORM\Column(type: 'string', length: 2000)]
    private $wardcd;

    #[ORM\Column(type: 'float')]
    private $perimeterM;

    #[ORM\Column(type: 'string', length: 100)]
    private $msoa11cd;

    #[ORM\Column(type: 'string', length: 200)]
    private $geoPoint2d;

    #[ORM\Column(type: 'float')]
    private $areaM2;

    #[ORM\Column(type: 'string', length: 100)]
    private $lsoa11nm;

    #[ORM\Column(type: 'string', length: 100)]
    private $lsoa11cd;

    #[ORM\Column(type: 'string', length: 100)]
    private $lsoa11ln;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBoundingPolygon()
    {
        return $this->boundingPolygon;
    }

    public function setBoundingPolygon($boundingPolygon): self
    {
        $this->boundingPolygon = $boundingPolygon;

        return $this;
    }

    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    public function setObjectId(int $objectId): self
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getWardcd(): ?string
    {
        return $this->wardcd;
    }

    public function setWardcd(string $wardcd): self
    {
        $this->wardcd = $wardcd;

        return $this;
    }

    public function getPerimeterM(): ?float
    {
        return $this->perimeterM;
    }

    public function setPerimeterM(float $perimeterM): self
    {
        $this->perimeterM = $perimeterM;

        return $this;
    }

    public function getMsoa11cd(): ?string
    {
        return $this->msoa11cd;
    }

    public function setMsoa11cd(string $msoa11cd): self
    {
        $this->msoa11cd = $msoa11cd;

        return $this;
    }

    public function getGeoPoint2d(): ?string
    {
        return $this->geoPoint2d;
    }

    public function setGeoPoint2d(string $geoPoint2d): self
    {
        $this->geoPoint2d = $geoPoint2d;

        return $this;
    }

    public function getAreaM2(): ?float
    {
        return $this->areaM2;
    }

    public function setAreaM2(float $areaM2): self
    {
        $this->areaM2 = $areaM2;

        return $this;
    }

    public function getLsoa11nm(): ?string
    {
        return $this->lsoa11nm;
    }

    public function setLsoa11nm(string $lsoa11nm): self
    {
        $this->lsoa11nm = $lsoa11nm;

        return $this;
    }

    public function getLsoa11cd(): ?string
    {
        return $this->lsoa11cd;
    }

    public function setLsoa11cd(string $lsoa11cd): self
    {
        $this->lsoa11cd = $lsoa11cd;

        return $this;
    }

    public function getLsoa11ln(): ?string
    {
        return $this->lsoa11ln;
    }

    public function setLsoa11ln(string $lsoa11ln): self
    {
        $this->lsoa11ln = $lsoa11ln;

        return $this;
    }

    public function getMiPrinx(): ?int
    {
        return $this->miPrinx;
    }

    public function setMiPrinx(int $miPrinx): self
    {
        $this->miPrinx = $miPrinx;

        return $this;
    }
}
