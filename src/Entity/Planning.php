<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
#[ORM\Table(name: 'planning')]
#[ORM\HasLifecycleCallbacks]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Seance::class)]
    #[ORM\JoinColumn(name: 'seance_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Seance $seance = null;

    #[ORM\Column(name: 'date_debut', type: 'datetime')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: 'datetime')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 50, options: ['default' => 'indigo'])]
    private ?string $color = 'indigo';

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $feedback = null;

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

    public function getSeance(): ?Seance { return $this->seance; }
    public function setSeance(?Seance $seance): self { $this->seance = $seance; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): self { $this->dateFin = $dateFin; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): self { $this->color = $color; return $this; }

    public function getFeedback(): ?int { return $this->feedback; }
    public function setFeedback(?int $feedback): self { $this->feedback = $feedback; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}