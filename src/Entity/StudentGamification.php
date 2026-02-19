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

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

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

    /**
     * Ajoute des points et recalcule le niveau automatiquement
     */
    public function addPoints(int $amount): static
    {
        $this->points += $amount;
        $this->updateLevel();
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

    /**
     * Calcule le niveau automatiquement selon les points
     * Niveau 1 = 0-99 XP | Niveau 2 = 100-199 XP | etc.
     */
    public function updateLevel(): void
    {
        $this->level = (int) floor($this->points / 100) + 1;
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

    public function getBadges(): array
    {
        return $this->badges ?? [];
    }

    public function setBadges(array $badges): static
    {
        $this->badges = $badges;
        return $this;
    }

    /**
     * Ajoute un badge si l'étudiant ne l'a pas encore
     */
    public function addBadge(string $name): static
    {
        $existing = array_column($this->badges ?? [], 'name');
        if (!in_array($name, $existing)) {
            $this->badges[] = [
                'name' => $name,
                'date' => (new \DateTime())->format('Y-m-d'),
            ];
        }
        return $this;
    }

    /**
     * Vérifie si l'étudiant possède déjà un badge
     */
    public function hasBadge(string $name): bool
    {
        $existing = array_column($this->badges ?? [], 'name');
        return in_array($name, $existing);
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
