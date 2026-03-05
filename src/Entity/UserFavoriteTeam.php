<?php
// src/Entity/UserFavoriteTeam.php

namespace App\Entity;

use App\Repository\UserFavoriteTeamRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFavoriteTeamRepository::class)]
#[ORM\Table(name: 'user_favorite_team')]
class UserFavoriteTeam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private ?string $sportType = null; // football, basketball, tennis, etc.

    #[ORM\Column(length: 100)]
    private ?string $teamName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $teamApiId = null; // ID de l'équipe dans l'API

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $teamCountry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $teamLogo = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters et Setters...
    
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

    public function getSportType(): ?string
    {
        return $this->sportType;
    }

    public function setSportType(string $sportType): static
    {
        $this->sportType = $sportType;
        return $this;
    }

    public function getTeamName(): ?string
    {
        return $this->teamName;
    }

    public function setTeamName(string $teamName): static
    {
        $this->teamName = $teamName;
        return $this;
    }

    public function getTeamApiId(): ?string
    {
        return $this->teamApiId;
    }

    public function setTeamApiId(?string $teamApiId): static
    {
        $this->teamApiId = $teamApiId;
        return $this;
    }

    public function getTeamCountry(): ?string
    {
        return $this->teamCountry;
    }

    public function setTeamCountry(?string $teamCountry): static
    {
        $this->teamCountry = $teamCountry;
        return $this;
    }

    public function getTeamLogo(): ?string
    {
        return $this->teamLogo;
    }

    public function setTeamLogo(?string $teamLogo): static
    {
        $this->teamLogo = $teamLogo;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}