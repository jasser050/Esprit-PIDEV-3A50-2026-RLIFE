<?php

namespace App\Entity;

use App\Repository\DeckRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeckRepository::class)]
#[ORM\Table(name: 'deck')]
class Deck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_deck', type: Types::INTEGER)]
    private ?int $idDeck = null;  // ← on renomme la propriété en $idDeck pour clarté

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'decks')]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $matiere = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $niveau = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $pdf = null;

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

    public function getIdDeck(): ?int
    {
        return $this->idDeck;
    }

    public function setIdDeck(int $idDeck): static
    {
        $this->idDeck = $idDeck;
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
}