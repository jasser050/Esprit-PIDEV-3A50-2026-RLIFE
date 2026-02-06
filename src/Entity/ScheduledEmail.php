<?php

namespace App\Entity;

use App\Repository\ScheduledEmailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduledEmailRepository::class)]
#[ORM\Table(name: 'scheduled_email')]
class ScheduledEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'admin_user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $adminUser = null;

    #[ORM\Column(name: 'recipient_type', type: Types::STRING, length: 50)]
    private ?string $recipientType = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(name: 'scheduled_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 20)]
    private ?string $status = 'pending'; // pending, sent, failed, cancelled

    #[ORM\Column(name: 'sent_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(name: 'recipient_count', type: Types::INTEGER, nullable: true)]
    private ?int $recipientCount = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
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

    public function getRecipientType(): ?string
    {
        return $this->recipientType;
    }

    public function setRecipientType(string $recipientType): static
    {
        $this->recipientType = $recipientType;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeInterface $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;
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

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getRecipientCount(): ?int
    {
        return $this->recipientCount;
    }

    public function setRecipientCount(?int $recipientCount): static
    {
        $this->recipientCount = $recipientCount;
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
     * Get human-readable label for recipient type
     */
    public function getRecipientTypeLabel(): string
    {
        return match ($this->recipientType) {
            'all' => 'All Users',
            'active' => 'Active Users',
            'banned' => 'Banned Users',
            'admins' => 'Administrators',
            default => ucfirst($this->recipientType),
        };
    }

    /**
     * Get badge color class for status
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-400',
            'sent' => 'bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-400',
            'failed' => 'bg-danger-100 dark:bg-danger-900/30 text-danger-700 dark:text-danger-400',
            'cancelled' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
            default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'sent' => 'Sent',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if email can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->status === 'pending' && $this->scheduledAt > new \DateTime();
    }
}
