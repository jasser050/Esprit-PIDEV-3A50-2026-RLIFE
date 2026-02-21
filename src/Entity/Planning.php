<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
<<<<<<< HEAD
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
=======
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
#[ORM\Table(name: 'planning')]
#[ORM\HasLifecycleCallbacks]
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

<<<<<<< HEAD
    #[ORM\ManyToOne(targetEntity: Seance::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Session selection is required.')]
    private ?Seance $seance = null;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotNull(message: 'Start date and time are required.')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotNull(message: 'End date and time are required.')]
    private ?\DateTimeInterface $dateFin = null;

    // Ajoute d’autres propriétés selon ton modèle :
    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: "smallint", nullable: true)]
    private ?int $feedback = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // 1) Date fin > début
        if ($this->dateDebut && $this->dateFin && $this->dateFin <= $this->dateDebut) {
            $context->buildViolation('The end date/time must be after the start.')
                ->atPath('dateFin')->addViolation();
        }

        // 2) Collision d’horaires dans la base (pas possible sans EntityManager ici, donc à faire côté controller)
        // On te montre la version controller plus bas.
    }

    // GETTERS/SETTERS

    public function getId(): ?int { return $this->id; }

    public function getSeance(): ?Seance { return $this->seance; }
    public function setSeance(?Seance $seance): self { $this->seance = $seance; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $dateFin): self { $this->dateFin = $dateFin; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): self { $this->color = $color; return $this; }

    public function getFeedback(): ?int { return $this->feedback; }
    public function setFeedback(?int $feedback): self { $this->feedback = $feedback; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function __toString(): string
    {
        return $this->seance ? $this->seance->__toString() : 'Planning #' . ($this->id ?? '?');
    }
}
=======
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Seance::class, inversedBy: 'plannings')]
    #[ORM\JoinColumn(name: 'seance_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Seance $seance = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSeance(): ?Seance
    {
        return $this->seance;
    }

    public function setSeance(?Seance $seance): static
    {
        $this->seance = $seance;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
