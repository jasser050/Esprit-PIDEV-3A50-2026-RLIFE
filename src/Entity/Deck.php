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
    private ?int $idDeck = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'decks')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Le titre doit contenir au moins 3 caractères", maxMessage: "Le titre ne peut pas dépasser 255 caractères")]
    #[Assert\Regex(pattern: "/^[^\d].*$/", message: "Le titre ne doit pas commencer par un chiffre")]
    private ?string $titre = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: "La matière est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "La matière doit contenir au moins 3 caractères")]
    private ?string $matiere = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: "Le niveau est obligatoire")]
    #[Assert\Length(min: 1, max: 50, minMessage: "Le niveau doit contenir au moins 1 caractère")]
    private ?string $niveau = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: "La description ne peut pas dépasser 2000 caractères")]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $pdf = null;

    #[Assert\File(
        maxSize: "5M",
        mimeTypes: ["image/jpeg", "image/png", "image/webp", "image/gif"],
        mimeTypesMessage: "Veuillez uploader une image valide (JPG, PNG, WEBP, GIF)",
        maxSizeMessage: "L'image ne doit pas dépasser 5 Mo"
    )]
    private ?File $imageFile = null;

    #[Assert\File(
        maxSize: "10M",
        mimeTypes: ["application/pdf"],
        mimeTypesMessage: "Veuillez uploader un fichier PDF valide",
        maxSizeMessage: "Le PDF ne doit pas dépasser 10 Mo"
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

    public function __construct()
    {
        $this->flashcards = new ArrayCollection();
        $this->revisionFlashcards = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    // ────────────────────────────────────────────────
    // Getters & Setters pour les fichiers (VALIDATION)
    // ────────────────────────────────────────────────

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

    // ────────────────────────────────────────────────
    // Getters & Setters pour les noms en base (STRING)
    // ────────────────────────────────────────────────

    public function getIdDeck(): ?int
    {
        return $this->idDeck;
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    // ✅ CORRECTION : Accepte null pour éviter l'erreur lors de la soumission du formulaire
    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getMatiere(): ?string
    {
        return $this->matiere;
    }

    // ✅ CORRECTION : Accepte null
    public function setMatiere(?string $matiere): self
    {
        $this->matiere = $matiere;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    // ✅ CORRECTION : Accepte null
    public function setNiveau(?string $niveau): self
    {
        $this->niveau = $niveau;
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getPdf(): ?string
    {
        return $this->pdf;
    }

    public function setPdf(?string $pdf): self
    {
        $this->pdf = $pdf;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
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

    public function addFlashcard(Flashcard $flashcard): self
    {
        if (!$this->flashcards->contains($flashcard)) {
            $this->flashcards->add($flashcard);
            $flashcard->setDeck($this);
        }

        return $this;
    }

    public function removeFlashcard(Flashcard $flashcard): self
    {
        if ($this->flashcards->removeElement($flashcard)) {
            // set the owning side to null (unless already changed)
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

    public function addRevisionFlashcard(Flashcard $revisionFlashcard): self
    {
        if (!$this->revisionFlashcards->contains($revisionFlashcard)) {
            $this->revisionFlashcards->add($revisionFlashcard);
        }

        return $this;
    }

    public function removeRevisionFlashcard(Flashcard $revisionFlashcard): self
    {
        $this->revisionFlashcards->removeElement($revisionFlashcard);

        return $this;
    }
}
