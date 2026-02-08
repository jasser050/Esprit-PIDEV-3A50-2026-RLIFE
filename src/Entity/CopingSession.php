<?php

namespace App\Entity;

use App\Repository\CopingSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CopingSessionRepository::class)]
#[ORM\Table(name: 'coping_session')]
#[ORM\HasLifecycleCallbacks]
class CopingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ nullable et sans inverse => pas besoin de changer User.php
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private string $toolKey;

    #[ORM\Column(length: 120)]
    private string $toolName;

    #[ORM\Column]
    private int $durationSeconds = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $startedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $actualSeconds = null;

    #[ORM\Column(length: 20)]
    private string $status = 'started'; // started|finished|cancelled

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->status = 'started';
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getToolKey(): string { return $this->toolKey; }
    public function setToolKey(string $toolKey): self { $this->toolKey = $toolKey; return $this; }

    public function getToolName(): string { return $this->toolName; }
    public function setToolName(string $toolName): self { $this->toolName = $toolName; return $this; }

    public function getDurationSeconds(): int { return $this->durationSeconds; }
    public function setDurationSeconds(int $durationSeconds): self { $this->durationSeconds = $durationSeconds; return $this; }

    public function getStartedAt(): \DateTimeInterface { return $this->startedAt; }
    public function setStartedAt(\DateTimeInterface $startedAt): self { $this->startedAt = $startedAt; return $this; }

    public function getFinishedAt(): ?\DateTimeInterface { return $this->finishedAt; }
    public function setFinishedAt(?\DateTimeInterface $finishedAt): self { $this->finishedAt = $finishedAt; return $this; }

    public function getActualSeconds(): ?int { return $this->actualSeconds; }
    public function setActualSeconds(?int $actualSeconds): self { $this->actualSeconds = $actualSeconds; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): self { $this->notes = $notes; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
