<?php

namespace App\Entity;

use App\Repository\CopingSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CopingSessionRepository::class)]
#[ORM\Table(name: 'coping_session')]
class CopingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'tool_key', length: 50)]
    private ?string $toolKey = null;

    #[ORM\Column(name: 'tool_name', length: 120)]
    private ?string $toolName = null;

    #[ORM\Column(name: 'duration_seconds')]
    private ?int $durationSeconds = null;

    #[ORM\Column(name: 'actual_seconds', nullable: true)]
    private ?int $actualSeconds = null;

    #[ORM\Column(name: 'status', length: 20)]
    private ?string $status = null;

    #[ORM\Column(name: 'started_at', length: 255)]
    private ?string $startedAt = null;

    #[ORM\Column(name: 'finished_at', length: 255, nullable: true)]
    private ?string $finishedAt = null;

    #[ORM\Column(name: 'created_at', length: 255)]
    private ?string $createdAt = null;

    #[ORM\Column(name: 'updated_at', length: 255, nullable: true)]
    private ?string $updatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToolKey(): ?string
    {
        return $this->toolKey;
    }

    public function setToolKey(string $toolKey): static
    {
        $this->toolKey = $toolKey;
        return $this;
    }

    public function getToolName(): ?string
    {
        return $this->toolName;
    }

    public function setToolName(string $toolName): static
    {
        $this->toolName = $toolName;
        return $this;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(int $durationSeconds): static
    {
        $this->durationSeconds = $durationSeconds;
        return $this;
    }

    public function getActualSeconds(): ?int
    {
        return $this->actualSeconds;
    }

    public function setActualSeconds(?int $actualSeconds): static
    {
        $this->actualSeconds = $actualSeconds;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStartedAt(): ?string
    {
        return $this->startedAt;
    }

    public function setStartedAt(string $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?string
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?string $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
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
}