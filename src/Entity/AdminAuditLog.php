<?php

namespace App\Entity;

use App\Repository\AdminAuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminAuditLogRepository::class)]
#[ORM\Table(name: 'admin_audit_log')]
class AdminAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'admin_user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $adminUser = null;

    #[ORM\Column(name: 'action_type', type: Types::STRING, length: 50)]
    private ?string $actionType = null;

    #[ORM\Column(name: 'target_type', type: Types::STRING, length: 50, nullable: true)]
    private ?string $targetType = null;

    #[ORM\Column(name: 'target_id', type: Types::INTEGER, nullable: true)]
    private ?int $targetId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', type: Types::STRING, length: 255, nullable: true)]
    private ?string $userAgent = null;

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

    public function getAdminUser(): ?User
    {
        return $this->adminUser;
    }

    public function setAdminUser(?User $adminUser): static
    {
        $this->adminUser = $adminUser;
        return $this;
    }

    public function getActionType(): ?string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): static
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getTargetType(): ?string
    {
        return $this->targetType;
    }

    public function setTargetType(?string $targetType): static
    {
        $this->targetType = $targetType;
        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function setTargetId(?int $targetId): static
    {
        $this->targetId = $targetId;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
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

    /**
     * Get a human-readable label for the action type
     */
    public function getActionLabel(): string
    {
        return match ($this->actionType) {
            'user_banned' => 'User Banned',
            'user_unbanned' => 'User Unbanned',
            'user_promoted' => 'User Promoted to Admin',
            'user_demoted' => 'Admin Role Removed',
            'project_deleted' => 'Project Deleted',
            'assignment_deleted' => 'Assignment Deleted',
            'deck_deleted' => 'Deck Deleted',
            'flashcard_deleted' => 'Flashcard Deleted',
            'email_sent' => 'Email Sent',
            default => ucfirst(str_replace('_', ' ', $this->actionType)),
        };
    }

    /**
     * Get badge color class based on action type
     */
    public function getBadgeColorClass(): string
    {
        return match ($this->actionType) {
            'user_banned', 'project_deleted', 'assignment_deleted', 'deck_deleted', 'flashcard_deleted' 
                => 'bg-danger-100 dark:bg-danger-900/30 text-danger-700 dark:text-danger-400',
            'user_unbanned', 'user_promoted' 
                => 'bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-400',
            'email_sent' 
                => 'bg-info-100 dark:bg-info-900/30 text-info-700 dark:text-info-400',
            default 
                => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
        };
    }

    /**
     * Get icon name for the action
     */
    public function getIcon(): string
    {
        return match ($this->actionType) {
            'user_banned' => 'user-x',
            'user_unbanned' => 'user-check',
            'user_promoted' => 'shield-check',
            'user_demoted' => 'shield-off',
            'project_deleted', 'assignment_deleted', 'deck_deleted', 'flashcard_deleted' => 'trash-2',
            'email_sent' => 'mail',
            default => 'activity',
        };
    }
}
