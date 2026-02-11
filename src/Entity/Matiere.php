<?php

namespace App\Entity;

use App\Repository\MatiereRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatiereRepository::class)]
#[ORM\Table(name: 'matiere')]
#[ORM\HasLifecycleCallbacks]
class Matiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_matiere', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'matieres')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'nom_matiere', type: Types::STRING, length: 255)]
    private ?string $nomMatiere = null;

    #[ORM\Column(name: 'coefficient_matiere', type: Types::FLOAT)]
    private ?float $coefficientMatiere = null;

    #[ORM\Column(name: 'section_matiere', type: Types::STRING, length: 255)]
    private ?string $sectionMatiere = null;

    #[ORM\Column(name: 'type_matiere', type: Types::STRING, length: 255)]
    private ?string $typeMatiere = null;

    #[ORM\Column(name: 'heure_matiere', type: Types::FLOAT)]
    private ?float $heureMatiere = null;

    #[ORM\Column(type: Types::STRING, length: 10, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
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

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getNomMatiere(): ?string
    {
        return $this->nomMatiere;
    }

    public function setNomMatiere(string $nomMatiere): static
    {
        $this->nomMatiere = $nomMatiere;
        return $this;
    }

    public function getCoefficientMatiere(): ?float
    {
        return $this->coefficientMatiere;
    }

    public function setCoefficientMatiere(float $coefficientMatiere): static
    {
        $this->coefficientMatiere = $coefficientMatiere;
        return $this;
    }

    public function getSectionMatiere(): ?string
    {
        return $this->sectionMatiere;
    }

    public function setSectionMatiere(string $sectionMatiere): static
    {
        $this->sectionMatiere = $sectionMatiere;
        return $this;
    }

    public function getTypeMatiere(): ?string
    {
        return $this->typeMatiere;
    }

    public function setTypeMatiere(string $typeMatiere): static
    {
        $this->typeMatiere = $typeMatiere;
        return $this;
    }

    public function getHeureMatiere(): ?float
    {
        return $this->heureMatiere;
    }

    public function setHeureMatiere(float $heureMatiere): static
    {
        $this->heureMatiere = $heureMatiere;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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