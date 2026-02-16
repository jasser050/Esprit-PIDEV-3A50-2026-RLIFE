<?php

namespace App\Entity;

use App\Repository\AdminEmailLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminEmailLogRepository::class)]
#[ORM\Table(name: 'admin_email_log')]
class AdminEmailLog
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: 'recipient_count', type: Types::INTEGER)]
    private ?int $recipientCount = null;

    #[ORM\Column(name: 'sent_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $sentAt = null;

    public function __construct()
    {
        $this->sentAt = new \DateTime();
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

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getRecipientCount(): ?int
    {
        return $this->recipientCount;
    }

    public function setRecipientCount(int $recipientCount): static
    {
        $this->recipientCount = $recipientCount;
        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;
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
     * Get badge color class for recipient type
     */
    public function getBadgeColorClass(): string
    {
        return match ($this->recipientType) {
            'all' => 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400',
            'active' => 'bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-400',
            'banned' => 'bg-danger-100 dark:bg-danger-900/30 text-danger-700 dark:text-danger-400',
            'admins' => 'bg-secondary-100 dark:bg-secondary-900/30 text-secondary-700 dark:text-secondary-400',
            default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
        };
    }
}
