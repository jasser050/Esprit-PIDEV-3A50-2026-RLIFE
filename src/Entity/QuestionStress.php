<?php

namespace App\Entity;

use App\Repository\QuestionStressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionStressRepository::class)]
class QuestionStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuizStress::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]  # Nullable pour permettre questions sans quiz
    private ?QuizStress $quiz = null;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column]
    private int $orderIndex = 1;

    #[ORM\Column]
    private int $minValue = 1;  # Changé à 1

    #[ORM\Column]
    private int $maxValue = 5;  # Changé à 5

    public function getId(): ?int { return $this->id; }

    public function getQuiz(): ?QuizStress { return $this->quiz; }
    public function setQuiz(?QuizStress $quiz): self { $this->quiz = $quiz; return $this; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): self { $this->label = $label; return $this; }

    public function getOrderIndex(): int { return $this->orderIndex; }
    public function setOrderIndex(int $orderIndex): self { $this->orderIndex = $orderIndex; return $this; }

    public function getMinValue(): int { return $this->minValue; }
    public function setMinValue(int $minValue): self { $this->minValue = $minValue; return $this; }

    public function getMaxValue(): int { return $this->maxValue; }
    public function setMaxValue(int $maxValue): self { $this->maxValue = $maxValue; return $this; }
}