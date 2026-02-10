<?php

namespace App\Entity;

use App\Repository\RecommendationStressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecommendationStressRepository::class)]
class RecommendationStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuizStress::class, inversedBy: 'recommendations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?QuizStress $quiz = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(length: 30)]
    private string $level = 'medium'; // low|medium|high

    public function getId(): ?int { return $this->id; }

    public function getQuiz(): ?QuizStress { return $this->quiz; }
    public function setQuiz(?QuizStress $quiz): self { $this->quiz = $quiz; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }

    public function getLevel(): string { return $this->level; }
    public function setLevel(string $level): self { $this->level = $level; return $this; }
}
