<?php

namespace App\Entity;

use App\Repository\MatiereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[ORM\Entity(repositoryClass: MatiereRepository::class)]
#[ORM\Table(name: 'matiere')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['code'],
    message: 'Ce code de matière existe déjà dans la base de données.'
)]
class Matiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_matiere', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'matieres')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;
#[ORM\Column(name: 'nom_matiere', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la matière est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères'
    )]
    private ?string $nomMatiere = null;

   #[ORM\Column(name: 'coefficient_matiere', type: 'float')]
    #[Assert\NotBlank(message: 'Le coefficient est obligatoire')]
    #[Assert\Positive(message: 'Le coefficient doit être un nombre positif')]
    #[Assert\Range(
        min: 0.1,
        max: 20,
        notInRangeMessage: 'Le coefficient doit être entre {{ min }} et {{ max }}'
    )]
    private ?float $coefficientMatiere = null;
     #[ORM\Column(name: 'section_matiere', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'La section est obligatoire')]
    #[Assert\Choice(
        choices: ['Science', 'Literature', 'Mathematics', 'Computer Science', 'Economics', 'Technology'],
        message: 'Section invalide. Choisissez parmi : Science, Literature, Mathematics, Computer Science, Economics, Technology'
    )]
    private ?string $sectionMatiere = null;

    #[ORM\Column(name: 'type_matiere', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le type de matière est obligatoire')]
    #[Assert\Choice(
        choices: ['Cours magistral', 'Travaux dirigés', 'Travaux pratiques'],
        message: 'Type de matière invalide. Choisissez parmi : Cours magistral, Travaux dirigés, Travaux pratiques'
    )]
    private ?string $typeMatiere = null;

    #[ORM\Column(name: 'heure_matiere', type: 'float')]
    #[Assert\NotBlank(message: 'Le nombre d\'heures est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le nombre d\'heures doit être positif ou zéro')]
    #[Assert\Range(
        min: 0,
        max: 40,
        notInRangeMessage: 'Le nombre d\'heures doit être entre {{ min }} et {{ max }}'
    )]
    private ?float $heureMatiere = null;
    #[ORM\Column(type: 'string', length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Le code est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 10,
        minMessage: 'Le code doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le code ne doit pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9-]+$/i',
        message: 'Le code ne peut contenir que des lettres, chiffres et tirets'
    )]
    private ?string $code = null;
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: EvalMat::class, mappedBy: 'matiere')]
private Collection $evalMats;


    public function __construct()
    {
        $this->evalMats = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

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

    public function getNomMatiere(): ?string
    {
        return $this->nomMatiere;
    }

    public function setNomMatiere(string $nomMatiere): self
    {
        $this->nomMatiere = $nomMatiere;
        return $this;
    }

    public function getCoefficientMatiere(): ?float
    {
        return $this->coefficientMatiere;
    }

    public function setCoefficientMatiere(float $coefficientMatiere): self
    {
        $this->coefficientMatiere = $coefficientMatiere;
        return $this;
    }

    public function getSectionMatiere(): ?string
    {
        return $this->sectionMatiere;
    }

    public function setSectionMatiere(string $sectionMatiere): self
    {
        $this->sectionMatiere = $sectionMatiere;
        return $this;
    }

    public function getTypeMatiere(): ?string
    {
        return $this->typeMatiere;
    }

    public function setTypeMatiere(string $typeMatiere): self
    {
        $this->typeMatiere = $typeMatiere;
        return $this;
    }

    public function getHeureMatiere(): ?float
    {
        return $this->heureMatiere;
    }

    public function setHeureMatiere(float $heureMatiere): self
    {
        $this->heureMatiere = $heureMatiere;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
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

    /**
     * @return Collection<int, EvalMat>
     */
    public function getEvalMats(): Collection
    {
        return $this->evalMats;
    }

    public function addEvalMat(EvalMat $evalMat): self
    {
        if (!$this->evalMats->contains($evalMat)) {
            $this->evalMats->add($evalMat);
            $evalMat->setMatiere($this);
        }

        return $this;
    }

    public function removeEvalMat(EvalMat $evalMat): self
    {
        if ($this->evalMats->removeElement($evalMat)) {
            if ($evalMat->getMatiere() === $this) {
                $evalMat->setMatiere(null);
            }
        }

        return $this;
    }
}
