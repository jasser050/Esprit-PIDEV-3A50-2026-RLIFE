<?php

namespace App\Entity;

use App\Repository\PetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetRepository::class)]
class Pet
{
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

    #[ORM\ManyToOne(inversedBy: 'pets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastHungerAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->lastHungerAt = $now;
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

    // Helper: pet mood label
    public function getMood(): string
    {
        if ($this->hunger > 70) return 'Hungry ??';
        if ($this->hunger > 30) return 'A little hungry ??';
        return 'Happy ??';
    }
}
