<?php

namespace App\Entity;

use App\Repository\QuizStressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizStressRepository::class)]
class QuizStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Optionnel mais conseillé (tu l'as déjà)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'integer')]
    private int $score = 0;

    #[ORM\Column(type: 'json')]
    private array $answers = []; // ex: [1=>2, 2=>4, ...]

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: QuestionStress::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: RecommendationStress::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $recommendations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->questions = new ArrayCollection();
        $this->recommendations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getScore(): int { return $this->score; }
    public function setScore(int $score): self { $this->score = $score; return $this; }

    public function getAnswers(): array { return $this->answers; }
    public function setAnswers(array $answers): self { $this->answers = $answers; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    /** @return Collection<int, QuestionStress> */
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
        return $this;
    }

    /** @return Collection<int, RecommendationStress> */
    public function getRecommendations(): Collection { return $this->recommendations; }

    public function addRecommendation(RecommendationStress $rec): self
    {
        if (!$this->recommendations->contains($rec)) {
            $this->recommendations->add($rec);
            $rec->setQuiz($this);
        }
        return $this;
    }

    public function removeRecommendation(RecommendationStress $rec): self
    {
        if ($this->recommendations->removeElement($rec)) {
            if ($rec->getQuiz() === $this) {
                $rec->setQuiz(null);
            }
        }
        return $this;
    }
}
