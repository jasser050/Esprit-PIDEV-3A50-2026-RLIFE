<?php

namespace App\Entity;

use App\Repository\AssignmentCollaboratorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssignmentCollaboratorRepository::class)]
#[ORM\Table(name: 'assignment_collaborator')]
#[ORM\HasLifecycleCallbacks]
class AssignmentCollaborator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Assignment::class, inversedBy: 'collaborators')]
    #[ORM\JoinColumn(name: 'assignment_id', nullable: false, onDelete: 'CASCADE')]
    private ?Assignment $assignment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_by_user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $assignedByUser = null;

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

    public function getAssignment(): ?Assignment
    {
        return $this->assignment;
    }

    public function setAssignment(?Assignment $assignment): static
    {
        $this->assignment = $assignment;
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

    public function getAssignedByUser(): ?User
    {
        return $this->assignedByUser;
    }

    public function setAssignedByUser(?User $assignedByUser): static
    {
        $this->assignedByUser = $assignedByUser;
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
