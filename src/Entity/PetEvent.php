<?php

namespace App\Entity;

use App\Repository\PetEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetEventRepository::class)]
#[ORM\Table(name: 'pet_event')]
class PetEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(name: 'pet_id', nullable: false, onDelete: 'CASCADE')]
    private ?Pet $pet = null;

    #[ORM\Column(length: 60)]
    private string $eventType = 'neutral';

    #[ORM\Column(length: 30)]
    private string $rarity = Pet::RARITY_COMMON;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $effects = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->effects = [];
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

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = trim($eventType) !== '' ? trim($eventType) : 'neutral';
        return $this;
    }

    public function getRarity(): string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): self
    {
        $this->rarity = mb_strtolower(trim($rarity)) ?: Pet::RARITY_COMMON;
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

    public function getEffects(): array
    {
        return is_array($this->effects) ? $this->effects : [];
    }

    public function setEffects(?array $effects): self
    {
        $this->effects = $effects ?? [];
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

