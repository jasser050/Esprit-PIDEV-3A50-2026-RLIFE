<?php

namespace App\Entity;

use App\Repository\PetAchievementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetAchievementRepository::class)]
#[ORM\Table(name: 'pet_achievement')]
class PetAchievement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(name: 'pet_id', nullable: false, onDelete: 'CASCADE')]
    private ?Pet $pet = null;

    #[ORM\Column(length: 100)]
    private string $code = '';

    #[ORM\Column(length: 160)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $rewardCoins = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $rewardXp = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $unlockedAt;

    public function __construct()
    {
        $this->unlockedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(?Pet $pet): self
    {
        $this->pet = $pet;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = trim($code);
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        return $this;
    }

    public function getRewardCoins(): int
    {
        return $this->rewardCoins;
    }

    public function setRewardCoins(int $rewardCoins): self
    {
        $this->rewardCoins = max(0, $rewardCoins);
        return $this;
    }

    public function getRewardXp(): int
    {
        return $this->rewardXp;
    }

    public function setRewardXp(int $rewardXp): self
    {
        $this->rewardXp = max(0, $rewardXp);
        return $this;
    }

    public function getUnlockedAt(): \DateTimeImmutable
    {
        return $this->unlockedAt;
    }
}

