<?php

namespace App\Entity;

use App\Repository\WellBeingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WellBeingRepository::class)]
#[ORM\Table(name: 'well_being')]
class WellBeing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'entry_date_well', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $entryDate = null;

    #[ORM\Column(name: 'mood_well', length: 50)]
    private ?string $mood = null;

    #[ORM\Column(name: 'stress_level_well')]
    private ?int $stressLevel = null;

    #[ORM\Column(name: 'energy_level_well')]
    private ?int $energyLevel = null;

    #[ORM\Column(name: 'sleep_hours_well', type: Types::FLOAT)]
    private ?float $sleepHours = null;

    #[ORM\Column(name: 'note_well', type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(name: 'created_at_well', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at_well', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntryDate(): ?\DateTimeInterface
    {
        return $this->entryDate;
    }

    public function setEntryDate(\DateTimeInterface $entryDate): static
    {
        $this->entryDate = $entryDate;
        return $this;
    }

    public function getMood(): ?string
    {
        return $this->mood;
    }

    public function setMood(string $mood): static
    {
        $this->mood = $mood;
        return $this;
    }

    public function getStressLevel(): ?int
    {
        return $this->stressLevel;
    }

    public function setStressLevel(int $stressLevel): static
    {
        $this->stressLevel = $stressLevel;
        return $this;
    }

    public function getEnergyLevel(): ?int
    {
        return $this->energyLevel;
    }

    public function setEnergyLevel(int $energyLevel): static
    {
        $this->energyLevel = $energyLevel;
        return $this;
    }

    public function getSleepHours(): ?float
    {
        return $this->sleepHours;
    }

    public function setSleepHours(float $sleepHours): static
    {
        $this->sleepHours = $sleepHours;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
