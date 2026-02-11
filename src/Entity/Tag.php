<?php

namespace App\Entity;

use Beelab\TagBundle\Tag\TagInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Tag implements TagInterface, \Stringable
{
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column]
    protected $name;

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}

