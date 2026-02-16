<?php

namespace App\Entity;

use App\Repository\DeckRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DeckRepository::class)]
#[ORM\Table(name: 'deck')]
class Deck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_deck', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'decks')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Title must be at least {{ limit }} characters',
        maxMessage: 'Title cannot exceed {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[^\d]/',
        message: 'Title must not start with a digit'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Subject is required')]
    #[Assert\Length(
        min: 3,
        minMessage: 'Subject must be at least {{ limit }} characters'
    )]
    private ?string $matiere = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: 'Level is required')]
    #[Assert\Length(
        min: 1,
        max: 50,
        minMessage: 'Level must be at least {{ limit }} character'
    )]
    private ?string $niveau = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'Description cannot exceed {{ limit }} characters'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $pdf = null;

    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        mimeTypesMessage: 'Please upload a valid image (JPG, PNG, WEBP, GIF)',
        maxSizeMessage: 'Image must not exceed 5 MB'
    )]
    private ?File $imageFile = null;

    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf'],
        mimeTypesMessage: 'Please upload a valid PDF file',
        maxSizeMessage: 'PDF must not exceed 10 MB'
    )]
    private ?File $pdfFile = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\OneToMany(targetEntity: Flashcard::class, mappedBy: 'deck', orphanRemoval: true)]
    private Collection $flashcards;

    #[ORM\ManyToMany(targetEntity: Flashcard::class)]
    #[ORM\JoinTable(name: 'revision_flashcard')]
    #[ORM\JoinColumn(name: 'id_deck', referencedColumnName: 'id_deck')]
    #[ORM\InverseJoinColumn(name: 'id_flashcard', referencedColumnName: 'id_flashcard')]
    private Collection $revisionFlashcards;

    private ?int $cardCount = null;

    private ?int $masteredCount = null;

    public function __construct()
    {
        $this->flashcards = new ArrayCollection();
        $this->revisionFlashcards = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;
        return $this;
    }

    public function getPdfFile(): ?File
    {
        return $this->pdfFile;
    }

    public function setPdfFile(?File $pdfFile): self
    {
        $this->pdfFile = $pdfFile;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getMatiere(): ?string
    {
        return $this->matiere;
    }

    public function setMatiere(string $matiere): static
    {
        $this->matiere = $matiere;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getPdf(): ?string
    {
        return $this->pdf;
    }

    public function setPdf(?string $pdf): static
    {
        $this->pdf = $pdf;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * @return Collection<int, Flashcard>
     */
    public function getFlashcards(): Collection
    {
        return $this->flashcards;
    }

    public function addFlashcard(Flashcard $flashcard): static
    {
        if (!$this->flashcards->contains($flashcard)) {
            $this->flashcards->add($flashcard);
            $flashcard->setDeck($this);
        }

        return $this;
    }

    public function removeFlashcard(Flashcard $flashcard): static
    {
        if ($this->flashcards->removeElement($flashcard)) {
            if ($flashcard->getDeck() === $this) {
                $flashcard->setDeck(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Flashcard>
     */
    public function getRevisionFlashcards(): Collection
    {
        return $this->revisionFlashcards;
    }

    public function addRevisionFlashcard(Flashcard $flashcard): static
    {
        if (!$this->revisionFlashcards->contains($flashcard)) {
            $this->revisionFlashcards->add($flashcard);
        }

        return $this;
    }

    public function removeRevisionFlashcard(Flashcard $flashcard): static
    {
        $this->revisionFlashcards->removeElement($flashcard);

        return $this;
    }

    public function getCardCount(): ?int
    {
        return $this->cardCount;
    }

    public function setCardCount(?int $cardCount): static
    {
        $this->cardCount = $cardCount;
        return $this;
    }

    public function getMasteredCount(): ?int
    {
        return $this->masteredCount;
    }

    public function setMasteredCount(?int $masteredCount): static
    {
        $this->masteredCount = $masteredCount;
        return $this;
    }
}
