<?php

namespace App\Entity;

use App\Repository\ProjectShareRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectShareRepository::class)]
#[ORM\Table(name: 'project_share')]
#[ORM\HasLifecycleCallbacks]
class ProjectShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'shared_with_user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $sharedWithUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'shared_by_user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $sharedByUser = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private ?string $role = 'viewer'; // viewer, editor

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getSharedWithUser(): ?User
    {
        return $this->sharedWithUser;
    }

    public function setSharedWithUser(?User $sharedWithUser): static
    {
        $this->sharedWithUser = $sharedWithUser;
        return $this;
    }

    public function getSharedByUser(): ?User
    {
        return $this->sharedByUser;
    }

    public function setSharedByUser(?User $sharedByUser): static
    {
        $this->sharedByUser = $sharedByUser;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
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
}