<?php

namespace App\Entity;

use App\Repository\RecommendationStressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecommendationStressRepository::class)]
class RecommendationStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $recommendationType_rec = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content_rec = null;

    #[ORM\Column(length: 50)]
    private ?string $priority_rec = null;

    #[ORM\Column]
    private ?\DateTime $generationDate_rec = null;

    #[ORM\Column(length: 100)]
    private ?string $source_rec = null;

    #[ORM\Column(length: 50)]
    private ?string $status_rec = null;

    #[ORM\Column]
    private ?\DateTime $createdAt_rec = null;

    #[ORM\ManyToOne(inversedBy: 'recommendations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuizStress $quizStress = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecommendationTypeRec(): ?string
    {
        return $this->recommendationType_rec;
    }

    public function setRecommendationTypeRec(string $recommendationType_rec): static
    {
        $this->recommendationType_rec = $recommendationType_rec;

        return $this;
    }

    public function getContentRec(): ?string
    {
        return $this->content_rec;
    }

    public function setContentRec(string $content_rec): static
    {
        $this->content_rec = $content_rec;

        return $this;
    }

    public function getPriorityRec(): ?string
    {
        return $this->priority_rec;
    }

    public function setPriorityRec(string $priority_rec): static
    {
        $this->priority_rec = $priority_rec;

        return $this;
    }

    public function getGenerationDateRec(): ?\DateTime
    {
        return $this->generationDate_rec;
    }

    public function setGenerationDateRec(\DateTime $generationDate_rec): static
    {
        $this->generationDate_rec = $generationDate_rec;

        return $this;
    }

    public function getSourceRec(): ?string
    {
        return $this->source_rec;
    }

    public function setSourceRec(string $source_rec): static
    {
        $this->source_rec = $source_rec;

        return $this;
    }

    public function getStatusRec(): ?string
    {
        return $this->status_rec;
    }

    public function setStatusRec(string $status_rec): static
    {
        $this->status_rec = $status_rec;

        return $this;
    }

    public function getCreatedAtRec(): ?\DateTime
    {
        return $this->createdAt_rec;
    }

    public function setCreatedAtRec(\DateTime $createdAt_rec): static
    {
        $this->createdAt_rec = $createdAt_rec;

        return $this;
    }

    public function getQuizStress(): ?QuizStress
    {
        return $this->quizStress;
    }

    public function setQuizStress(?QuizStress $quizStress): static
    {
        $this->quizStress = $quizStress;

        return $this;
    }
}
