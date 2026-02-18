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

<<<<<<< HEAD
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
=======
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $entryDate_well = null;

    #[ORM\Column(length: 50)]
    private ?string $mood_well = null;

    #[ORM\Column]
    private ?int $stressLevel_well = null;

    #[ORM\Column]
    private ?int $energyLevel_well = null;

    #[ORM\Column]
    private ?float $sleepHours_well = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note_well = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt_well = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt_well = null;

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd

    public function getId(): ?int
    {
        return $this->id;
    }

<<<<<<< HEAD
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
=======
    public function getEntryDateWell(): ?\DateTimeInterface
    {
        return $this->entryDate_well;
    }

    public function setEntryDateWell(\DateTimeInterface $entryDate_well): self
    {
        $this->entryDate_well = $entryDate_well;
        return $this;
    }

    public function getMoodWell(): ?string
    {
        return $this->mood_well;
    }

    public function setMoodWell(string $mood_well): self
    {
        $this->mood_well = $mood_well;
        return $this;
    }

    public function getStressLevelWell(): ?int
    {
        return $this->stressLevel_well;
    }

    public function setStressLevelWell(int $stressLevel_well): self
    {
        $this->stressLevel_well = $stressLevel_well;
        return $this;
    }

    public function getEnergyLevelWell(): ?int
    {
        return $this->energyLevel_well;
    }

    public function setEnergyLevelWell(int $energyLevel_well): self
    {
        $this->energyLevel_well = $energyLevel_well;
        return $this;
    }

    public function getSleepHoursWell(): ?float
    {
        return $this->sleepHours_well;
    }

    public function setSleepHoursWell(float $sleepHours_well): self
    {
        $this->sleepHours_well = $sleepHours_well;
        return $this;
    }

    public function getNoteWell(): ?string
    {
        return $this->note_well;
    }

    public function setNoteWell(?string $note_well): self
    {
        $this->note_well = $note_well;
        return $this;
    }

    public function getCreatedAtWell(): ?\DateTimeInterface
    {
        return $this->createdAt_well;
    }

    public function setCreatedAtWell(\DateTimeInterface $createdAt_well): self
    {
        $this->createdAt_well = $createdAt_well;
        return $this;
    }

    public function getUpdatedAtWell(): ?\DateTimeInterface
    {
        return $this->updatedAt_well;
    }

    public function setUpdatedAtWell(?\DateTimeInterface $updatedAt_well): self
    {
        $this->updatedAt_well = $updatedAt_well;
        return $this;
    }
}
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
