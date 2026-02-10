<?php

namespace App\Entity;

use App\Repository\TypeSeanceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeSeanceRepository::class)]
#[ORM\Table(name: 'type_seance')]
#[ORM\UniqueConstraint(name: 'uniq_type_seance_name', columns: ['name'])]
class TypeSeance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

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

    public function __toString(): string
    {
        return (string) ($this->name ?? ('Type #' . ($this->id ?? '')));
    }
}