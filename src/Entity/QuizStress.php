<?php

namespace App\Entity;

use App\Repository\QuizStressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizStressRepository::class)]
class QuizStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $quizDate_quiz = null;

    #[ORM\Column]
    private array $answers_quiz = [];

    #[ORM\Column]
    private ?int $totalScore_quiz = null;

    #[ORM\Column(length: 50)]
    private ?string $stressLevel_quiz = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $interpretation_quiz = null;

    #[ORM\Column]
    private ?bool $createdWithAI_quiz = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aiModel_quiz = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aiPromptVersion_quiz = null;

    #[ORM\Column]
    private ?\DateTime $createdAt_quiz = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt_quiz = null;

    /**
     * @var Collection<int, RecommendationStress>
     */
    #[ORM\OneToMany(targetEntity: RecommendationStress::class, mappedBy: 'quizStress')]
    private Collection $recommendations;

    public function __construct()
    {
        $this->recommendations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuizDateQuiz(): ?\DateTime
    {
        return $this->quizDate_quiz;
    }

    public function setQuizDateQuiz(\DateTime $quizDate_quiz): static
    {
        $this->quizDate_quiz = $quizDate_quiz;

        return $this;
    }

    public function getAnswersQuiz(): array
    {
        return $this->answers_quiz;
    }

    public function setAnswersQuiz(array $answers_quiz): static
    {
        $this->answers_quiz = $answers_quiz;

        return $this;
    }

    public function getTotalScoreQuiz(): ?int
    {
        return $this->totalScore_quiz;
    }

    public function setTotalScoreQuiz(int $totalScore_quiz): static
    {
        $this->totalScore_quiz = $totalScore_quiz;

        return $this;
    }

    public function getStressLevelQuiz(): ?string
    {
        return $this->stressLevel_quiz;
    }

    public function setStressLevelQuiz(string $stressLevel_quiz): static
    {
        $this->stressLevel_quiz = $stressLevel_quiz;

        return $this;
    }

    public function getInterpretationQuiz(): ?string
    {
        return $this->interpretation_quiz;
    }

    public function setInterpretationQuiz(string $interpretation_quiz): static
    {
        $this->interpretation_quiz = $interpretation_quiz;

        return $this;
    }

    public function isCreatedWithAIQuiz(): ?bool
    {
        return $this->createdWithAI_quiz;
    }

    public function setCreatedWithAIQuiz(bool $createdWithAI_quiz): static
    {
        $this->createdWithAI_quiz = $createdWithAI_quiz;

        return $this;
    }

    public function getAiModelQuiz(): ?string
    {
        return $this->aiModel_quiz;
    }

    public function setAiModelQuiz(?string $aiModel_quiz): static
    {
        $this->aiModel_quiz = $aiModel_quiz;

        return $this;
    }

    public function getAiPromptVersionQuiz(): ?string
    {
        return $this->aiPromptVersion_quiz;
    }

    public function setAiPromptVersionQuiz(?string $aiPromptVersion_quiz): static
    {
        $this->aiPromptVersion_quiz = $aiPromptVersion_quiz;

        return $this;
    }

    public function getCreatedAtQuiz(): ?\DateTime
    {
        return $this->createdAt_quiz;
    }

    public function setCreatedAtQuiz(\DateTime $createdAt_quiz): static
    {
        $this->createdAt_quiz = $createdAt_quiz;

        return $this;
    }

    public function getUpdatedAtQuiz(): ?\DateTime
    {
        return $this->updatedAt_quiz;
    }

    public function setUpdatedAtQuiz(?\DateTime $updatedAt_quiz): static
    {
        $this->updatedAt_quiz = $updatedAt_quiz;

        return $this;
    }

    /**
     * @return Collection<int, RecommendationStress>
     */
    public function getRecommendations(): Collection
    {
        return $this->recommendations;
    }

    public function addRecommendation(RecommendationStress $recommendation): static
    {
        if (!$this->recommendations->contains($recommendation)) {
            $this->recommendations->add($recommendation);
            $recommendation->setQuizStress($this);
        }

        return $this;
    }

    public function removeRecommendation(RecommendationStress $recommendation): static
    {
        if ($this->recommendations->removeElement($recommendation)) {
            // set the owning side to null (unless already changed)
            if ($recommendation->getQuizStress() === $this) {
                $recommendation->setQuizStress(null);
            }
        }

        return $this;
    }
}
