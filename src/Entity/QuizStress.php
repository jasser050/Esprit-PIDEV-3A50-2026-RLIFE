<?php

namespace App\Entity;

use App\Repository\QuizStressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizStressRepository::class)]
#[ORM\Table(name: 'quiz_stress')]
class QuizStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'quiz_date_quiz', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $quizDate = null;

    #[ORM\Column(name: 'answers_quiz', type: Types::JSON)]
    private array $answers = [];

    #[ORM\Column(name: 'total_score_quiz')]
    private ?int $totalScore = null;

    #[ORM\Column(name: 'stress_level_quiz', length: 50)]
    private ?string $stressLevel = null;

    #[ORM\Column(name: 'interpretation_quiz', type: Types::TEXT)]
    private ?string $interpretation = null;

    #[ORM\Column(name: 'created_with_ai_quiz')]
    private ?bool $createdWithAi = null;

    #[ORM\Column(name: 'ai_model_quiz', length: 255, nullable: true)]
    private ?string $aiModel = null;

    #[ORM\Column(name: 'ai_prompt_version_quiz', length: 255, nullable: true)]
    private ?string $aiPromptVersion = null;

    #[ORM\Column(name: 'created_at_quiz', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at_quiz', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuizDate(): ?\DateTimeInterface
    {
        return $this->quizDate;
    }

    public function setQuizDate(\DateTimeInterface $quizDate): static
    {
        $this->quizDate = $quizDate;
        return $this;
    }

    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers): static
    {
        $this->answers = $answers;
        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function setTotalScore(int $totalScore): static
    {
        $this->totalScore = $totalScore;
        return $this;
    }

    public function getStressLevel(): ?string
    {
        return $this->stressLevel;
    }

    public function setStressLevel(string $stressLevel): static
    {
        $this->stressLevel = $stressLevel;
        return $this;
    }

    public function getInterpretation(): ?string
    {
        return $this->interpretation;
    }

    public function setInterpretation(string $interpretation): static
    {
        $this->interpretation = $interpretation;
        return $this;
    }

    public function isCreatedWithAi(): ?bool
    {
        return $this->createdWithAi;
    }

    public function setCreatedWithAi(bool $createdWithAi): static
    {
        $this->createdWithAi = $createdWithAi;
        return $this;
    }

    public function getAiModel(): ?string
    {
        return $this->aiModel;
    }

    public function setAiModel(?string $aiModel): static
    {
        $this->aiModel = $aiModel;
        return $this;
    }

    public function getAiPromptVersion(): ?string
    {
        return $this->aiPromptVersion;
    }

    public function setAiPromptVersion(?string $aiPromptVersion): static
    {
        $this->aiPromptVersion = $aiPromptVersion;
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