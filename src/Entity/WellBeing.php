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


    public function getId(): ?int
    {
        return $this->id;
    }

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
