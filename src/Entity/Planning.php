<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Seance::class, inversedBy: 'plannings')]
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
    #[ORM\Column(type: "string", length: 20)]
    #[Assert\NotNull(message: 'Color selection is required.')]
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