<?php

namespace App\Entity;

use App\Repository\QuestionStressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionStressRepository::class)]
#[ORM\Table(name: 'question_stress')]
class QuestionStress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'position')]
    #[Assert\Positive(message: 'La position doit être supérieure à 0.')]
    private int $position = 0;

    #[ORM\Column(name: 'question_text_ques', type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le champ question est obligatoire et ne peut pas être vide.')]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'La question doit contenir au minimum {{ limit }} caractères.',
        maxMessage: 'La question ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $questionText = null;

    #[ORM\Column(name: 'is_active_ques')]
    private ?bool $isActive = false;

    #[ORM\Column(name: 'created_at_ques', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at_ques', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getQuestionText(): ?string
    {
        return $this->questionText;
    }

    public function setQuestionText(string $questionText): static
    {
        $this->questionText = $questionText;
        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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