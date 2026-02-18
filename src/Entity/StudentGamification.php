<?php

namespace App\Entity;

use App\Repository\StudentGamificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentGamificationRepository::class)]
#[ORM\Table(name: 'student_gamification')]
class StudentGamification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Deck::class)]
    #[ORM\JoinColumn(name: 'deck_id', referencedColumnName: 'id_deck', nullable: true, onDelete: 'SET NULL')]
    private ?Deck $deck = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $points = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $level = 1;

    #[ORM\Column(type: Types::INTEGER)]
    private int $streak = 0;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastActivity = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $badges = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalDecksCompleted = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalCorrectAnswers = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

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

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): static
    {
        $this->deck = $deck;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getStreak(): int
    {
        return $this->streak;
    }

    public function setStreak(int $streak): static
    {
        $this->streak = $streak;
        return $this;
    }

    public function getLastActivity(): ?\DateTimeInterface
    {
        return $this->lastActivity;
    }

    public function setLastActivity(?\DateTimeInterface $lastActivity): static
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }

    public function getBadges(): ?array
    {
        return $this->badges;
    }

    public function setBadges(?array $badges): static
    {
        $this->badges = $badges;
        return $this;
    }

    public function getTotalDecksCompleted(): int
    {
        return $this->totalDecksCompleted;
    }

    public function setTotalDecksCompleted(int $totalDecksCompleted): static
    {
        $this->totalDecksCompleted = $totalDecksCompleted;
        return $this;
    }

    public function getTotalCorrectAnswers(): int
    {
        return $this->totalCorrectAnswers;
    }

    public function setTotalCorrectAnswers(int $totalCorrectAnswers): static
    {
        $this->totalCorrectAnswers = $totalCorrectAnswers;
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
