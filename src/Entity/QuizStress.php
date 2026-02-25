<?php

namespace App\Entity;

use App\Repository\QuizStressRepository;
<<<<<<< HEAD
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizStressRepository::class)]
#[ORM\Table(name: 'quiz_stress')]
=======
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizStressRepository::class)]
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
class QuizStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

<<<<<<< HEAD
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
=======
    #[ORM\Column(length: 255)]
    private string $title = 'Stress Assessment';

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $score = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $answers = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: QuestionStress::class, cascade: ['persist', 'remove'])]
    private Collection $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters & Setters (inchangés sauf title par défaut en anglais)
    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getScore(): int { return $this->score; }
    public function setScore(int $score): self { $this->score = $score; return $this; }

    public function getAnswers(): ?array { return $this->answers; }
    public function setAnswers(?array $answers): self { $this->answers = $answers; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    /**
     * @return Collection<int, QuestionStress>
     */
    public function getQuestions(): Collection { return $this->questions; }

    public function addQuestion(QuestionStress $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this);
        }
        return $this;
    }

    public function removeQuestion(QuestionStress $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
        return $this;
    }
}