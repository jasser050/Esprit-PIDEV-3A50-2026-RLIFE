<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: 'rating')]
#[ORM\UniqueConstraint(name: 'unique_user_deck_rating', columns: ['user_id', 'deck_id'])]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Deck::class)]
    #[ORM\JoinColumn(name: 'deck_id', referencedColumnName: 'id_deck', nullable: false, onDelete: 'CASCADE')]
    private ?Deck $deck = null;

    #[ORM\Column(name: 'stars', type: Types::INTEGER)]
    #[Assert\NotNull(message: "La note est obligatoire")]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "La note doit être entre {{ min }} et {{ max }} étoiles"
    )]
    private ?int $stars = null;

    // ── Critères avancés (optionnels) ──
    #[ORM\Column(name: 'clarity', type: Types::SMALLINT, nullable: true)]
    private ?int $clarity = null;

    #[ORM\Column(name: 'completeness', type: Types::SMALLINT, nullable: true)]
    private ?int $completeness = null;

    #[ORM\Column(name: 'difficulty', type: Types::SMALLINT, nullable: true)]
    private ?int $difficulty = null;

    #[ORM\Column(name: 'usefulness', type: Types::SMALLINT, nullable: true)]
    private ?int $usefulness = null;

    // ── Tags (tableau JSON, optionnel) ──
    #[ORM\Column(name: 'tags', type: Types::JSON, nullable: true)]
    private ?array $tags = null;

    // ── Commentaire libre (optionnel, max 500 chars) ──
    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères")]
    private ?string $comment = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ── Getters / Setters ──

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): self
    {
        $this->deck = $deck;
        return $this;
    }

    public function getStars(): ?int
    {
        return $this->stars;
    }

    public function setStars(?int $stars): self
    {
        $this->stars = $stars;
        return $this;
    }

    public function getClarity(): ?int
    {
        return $this->clarity;
    }

    public function setClarity(?int $clarity): self
    {
        $this->clarity = $clarity;
        return $this;
    }

    public function getCompleteness(): ?int
    {
        return $this->completeness;
    }

    public function setCompleteness(?int $completeness): self
    {
        $this->completeness = $completeness;
        return $this;
    }

    public function getDifficulty(): ?int
    {
        return $this->difficulty;
    }

    public function setDifficulty(?int $difficulty): self
    {
        $this->difficulty = $difficulty;
        return $this;
    }

    public function getUsefulness(): ?int
    {
        return $this->usefulness;
    }

    public function setUsefulness(?int $usefulness): self
    {
        $this->usefulness = $usefulness;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}