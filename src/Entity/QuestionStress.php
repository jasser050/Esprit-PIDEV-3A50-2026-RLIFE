<?php

namespace App\Entity;

use App\Repository\QuestionStressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionStressRepository::class)]
class QuestionStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $questionNumber_ques = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $questionText_ques = null;

    #[ORM\Column]
    private ?bool $isActive_ques = null;

    #[ORM\Column]
    private ?\DateTime $createdAt_ques = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt_ques = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestionNumberQues(): ?int
    {
        return $this->questionNumber_ques;
    }

    public function setQuestionNumberQues(int $questionNumber_ques): static
    {
        $this->questionNumber_ques = $questionNumber_ques;

        return $this;
    }

    public function getQuestionTextQues(): ?string
    {
        return $this->questionText_ques;
    }

    public function setQuestionTextQues(string $questionText_ques): static
    {
        $this->questionText_ques = $questionText_ques;

        return $this;
    }

    public function isActiveQues(): ?bool
    {
        return $this->isActive_ques;
    }

    public function setIsActiveQues(bool $isActive_ques): static
    {
        $this->isActive_ques = $isActive_ques;

        return $this;
    }

    public function getCreatedAtQues(): ?\DateTime
    {
        return $this->createdAt_ques;
    }

    public function setCreatedAtQues(\DateTime $createdAt_ques): static
    {
        $this->createdAt_ques = $createdAt_ques;

        return $this;
    }

    public function getUpdatedAtQues(): ?\DateTime
    {
        return $this->updatedAt_ques;
    }

    public function setUpdatedAtQues(?\DateTime $updatedAt_ques): static
    {
        $this->updatedAt_ques = $updatedAt_ques;

        return $this;
    }
}
