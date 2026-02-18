<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
#[ORM\Table(name: 'seance')]
#[ORM\HasLifecycleCallbacks]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'User is required.')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Title must be at least {{ limit }} characters long.',
        maxMessage: 'Title cannot exceed {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}]+(?: [\p{L}\p{N}]+)*$/u',
        message: 'Title can contain only letters, numbers, and spaces.'
    )]
    private ?string $titre = null;

    #[ORM\ManyToOne(targetEntity: TypeSeance::class)]
    #[ORM\JoinColumn(name: 'type_seance_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Please select a session type.')]
    private ?TypeSeance $typeSeance = null;

    #[ORM\Column(type: 'text', nullable: false)]
    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(
        min: 5,
        max: 1000,
        minMessage: 'Description must be at least {{ limit }} characters long.',
        maxMessage: 'Description cannot exceed {{ limit }} characters.'
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $this->updatedAt ?? $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): self { $this->titre = $titre; return $this; }

    public function getTypeSeance(): ?TypeSeance { return $this->typeSeance; }
    public function setTypeSeance(?TypeSeance $typeSeance): self { $this->typeSeance = $typeSeance; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function __toString(): string
    {
        return $this->titre ?? ('Seance #' . ($this->id ?? ''));
    }
}