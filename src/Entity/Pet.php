<?php

namespace App\Entity;

use App\Repository\PetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetRepository::class)]
class Pet
{
    public const PERSONALITY_PLAYFUL = 'playful';
    public const PERSONALITY_LAZY = 'lazy';
    public const PERSONALITY_AGGRESSIVE = 'aggressive';
    public const PERSONALITY_CALM = 'calm';

    public const RARITY_COMMON = 'common';
    public const RARITY_RARE = 'rare';
    public const RARITY_EPIC = 'epic';
    public const RARITY_LEGENDARY = 'legendary';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // 'cat', 'dog', 'dragon', 'fox', etc.

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column]
    private int $level = 1;

    #[ORM\Column]
    private int $hunger = 100; // 0 = full, 100 = hungry

    #[ORM\Column]
    private int $coinsSpent = 0; // Total coins spent for this pet

    #[ORM\Column(length: 30, options: ['default' => self::PERSONALITY_CALM])]
    private string $personality = self::PERSONALITY_CALM;

    #[ORM\Column(length: 20, options: ['default' => self::RARITY_COMMON])]
    private string $rarity = self::RARITY_COMMON;

    #[ORM\Column(options: ['default' => 0])]
    private int $xp = 0;

    #[ORM\Column(options: ['default' => 70])]
    private int $happiness = 70;

    #[ORM\Column(options: ['default' => 80])]
    private int $energy = 80;

    #[ORM\Column(options: ['default' => 100])]
    private int $health = 100;

    #[ORM\ManyToOne(inversedBy: 'pets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastHungerAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastInteractionAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastEventAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $stateFlags = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->lastHungerAt = $now;
        $this->lastInteractionAt = $now;
        $this->stateFlags = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getHunger(): int
    {
        return $this->hunger;
    }

    public function setHunger(int $hunger): self
    {
        $this->hunger = max(0, min(100, $hunger));
        return $this;
    }

    public function getCoinsSpent(): int
    {
        return $this->coinsSpent;
    }

    public function addCoinsSpent(int $amount): self
    {
        $this->coinsSpent += $amount;
        return $this;
    }

    public function getPersonality(): string
    {
        return $this->personality;
    }

    public function setPersonality(string $personality): self
    {
        $allowed = [
            self::PERSONALITY_PLAYFUL,
            self::PERSONALITY_LAZY,
            self::PERSONALITY_AGGRESSIVE,
            self::PERSONALITY_CALM,
        ];
        $normalized = mb_strtolower(trim($personality));
        $this->personality = in_array($normalized, $allowed, true) ? $normalized : self::PERSONALITY_CALM;
        return $this;
    }

    public function getRarity(): string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): self
    {
        $allowed = [self::RARITY_COMMON, self::RARITY_RARE, self::RARITY_EPIC, self::RARITY_LEGENDARY];
        $normalized = mb_strtolower(trim($rarity));
        $this->rarity = in_array($normalized, $allowed, true) ? $normalized : self::RARITY_COMMON;
        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): self
    {
        $this->xp = max(0, $xp);
        return $this;
    }

    public function addXp(int $xp): self
    {
        if ($xp > 0) {
            $this->xp += $xp;
        }
        return $this;
    }

    public function getHappiness(): int
    {
        return $this->happiness;
    }

    public function setHappiness(int $happiness): self
    {
        $this->happiness = max(0, min(100, $happiness));
        return $this;
    }

    public function getEnergy(): int
    {
        return $this->energy;
    }

    public function setEnergy(int $energy): self
    {
        $this->energy = max(0, min(100, $energy));
        return $this;
    }

    public function getHealth(): int
    {
        return $this->health;
    }

    public function setHealth(int $health): self
    {
        $this->health = max(0, min(100, $health));
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastHungerAt(): \DateTimeImmutable
    {
        return $this->lastHungerAt;
    }

    public function setLastHungerAt(\DateTimeImmutable $lastHungerAt): self
    {
        $this->lastHungerAt = $lastHungerAt;
        return $this;
    }

    public function getLastInteractionAt(): ?\DateTimeImmutable
    {
        return $this->lastInteractionAt;
    }

    public function setLastInteractionAt(?\DateTimeImmutable $lastInteractionAt): self
    {
        $this->lastInteractionAt = $lastInteractionAt;
        return $this;
    }

    public function getLastEventAt(): ?\DateTimeImmutable
    {
        return $this->lastEventAt;
    }

    public function setLastEventAt(?\DateTimeImmutable $lastEventAt): self
    {
        $this->lastEventAt = $lastEventAt;
        return $this;
    }

    public function getStateFlags(): array
    {
        return is_array($this->stateFlags) ? $this->stateFlags : [];
    }

    public function setStateFlags(?array $stateFlags): self
    {
        $this->stateFlags = $stateFlags ?? [];
        return $this;
    }

    public function setStateFlag(string $key, mixed $value): self
    {
        $flags = $this->getStateFlags();
        $flags[$key] = $value;
        $this->stateFlags = $flags;
        return $this;
    }

    public function getStateFlag(string $key, mixed $default = null): mixed
    {
        $flags = $this->getStateFlags();
        return $flags[$key] ?? $default;
    }

    // Helper: pet mood label
    public function getMood(): string
    {
        if ($this->health <= 25) return 'Sick';
        if ($this->energy <= 20) return 'Tired';
        if ($this->hunger >= 80) return 'Hungry';
        if ($this->happiness >= 80 && $this->energy >= 60) return 'Excited';
        if ($this->happiness <= 30) return 'Sad';
        return 'Calm';
    }

    public function getXpToNextLevel(): int
    {
        return $this->xpForLevel($this->level + 1);
    }

    public function canLevelUp(): bool
    {
        return $this->xp >= $this->getXpToNextLevel();
    }

    public function tryLevelUp(): bool
    {
        if (!$this->canLevelUp()) {
            return false;
        }

        $this->level += 1;
        $this->happiness = min(100, $this->happiness + 8);
        $this->energy = min(100, $this->energy + 6);
        $this->health = min(100, $this->health + 5);
        return true;
    }

    private function xpForLevel(int $level): int
    {
        $lv = max(1, $level);
        return (int) round(100 * $lv * (1 + (($lv - 1) * 0.12)));
    }
}
