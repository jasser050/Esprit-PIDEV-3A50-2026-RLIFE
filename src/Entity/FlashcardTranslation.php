<?php

namespace App\Entity;

use App\Repository\FlashcardTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stocke les traductions de flashcards.
 *
 * Une flashcard peut avoir plusieurs traductions (1 par langue).
 */
#[ORM\Entity(repositoryClass: FlashcardTranslationRepository::class)]
#[ORM\Table(name: 'flashcard_translation')]
#[ORM\Index(name: 'idx_flashcard_lang', columns: ['flashcard_id', 'language'])]
#[ORM\Index(name: 'idx_language', columns: ['language'])]
class FlashcardTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Flashcard::class)]
    #[ORM\JoinColumn(name: 'flashcard_id', referencedColumnName: 'id_flashcard', nullable: false, onDelete: 'CASCADE')]
    private ?Flashcard $flashcard = null;

    /**
     * Code ISO 639-1 de la langue (fr, en, ar, es, etc.)
     */
    #[ORM\Column(type: Types::STRING, length: 5)]
    private ?string $language = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $question = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $reponse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Niveau de difficulté ajusté pour cette langue (0-5)
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $difficultyLevel = null;

    /**
     * Notes du traducteur (adaptations culturelles, etc.)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $translatorNotes = null;

    /**
     * Date de création de la traduction
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * Dernière modification
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * Qualité estimée de la traduction (0-100)
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $qualityScore = null;

    /**
     * Si true, traduction vérifiée par un humain
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isVerified = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ─────────────────────────────────────────────────────────────
    // GETTERS & SETTERS
    // ─────────────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFlashcard(): ?Flashcard
    {
        return $this->flashcard;
    }

    /**
     * FIX: nullable accepté pour que Flashcard::removeTranslation() puisse détacher proprement
     */
    public function setFlashcard(?Flashcard $flashcard): self
    {
        $this->flashcard = $flashcard;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(string $reponse): self
    {
        $this->reponse = $reponse;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDifficultyLevel(): ?int
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(?int $difficultyLevel): self
    {
        $this->difficultyLevel = $difficultyLevel;
        return $this;
    }

    public function getTranslatorNotes(): ?string
    {
        return $this->translatorNotes;
    }

    public function setTranslatorNotes(?string $translatorNotes): self
    {
        $this->translatorNotes = $translatorNotes;
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

    public function getQualityScore(): ?int
    {
        return $this->qualityScore;
    }

    public function setQualityScore(?int $qualityScore): self
    {
        $this->qualityScore = $qualityScore;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * Retourne le drapeau emoji de la langue
     */
    public function getLanguageFlag(): string
    {
        $flags = [
            'fr' => '🇫🇷',
            'en' => '🇬🇧',
            'ar' => '🇸🇦',
            'es' => '🇪🇸',
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            'zh' => '🇨🇳',
            'ja' => '🇯🇵',
            'ru' => '🇷🇺',
            'pt' => '🇵🇹',
        ];

        return $flags[$this->language] ?? '🌐';
    }

    /**
     * Retourne le nom de la langue
     */
    public function getLanguageName(): string
    {
        $names = [
            'fr' => 'Français',
            'en' => 'English',
            'ar' => 'العربية',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'zh' => '中文',
            'ja' => '日本語',
            'ru' => 'Русский',
            'pt' => 'Português',
        ];

        return $names[$this->language] ?? $this->language;
    }
}