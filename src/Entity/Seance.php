<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Title is required")]
    #[Assert\Length(min: 3, minMessage: "Title must be at least 3 characters")]
    private ?string $titre = null;

    #[ORM\ManyToOne(targetEntity: TypeSeance::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: "Session type is required")]
    private ?TypeSeance $typeSeance = null;

    #[ORM\ManyToOne(targetEntity: Matiere::class)]
    #[ORM\JoinColumn(name: "matiere_id", referencedColumnName: "id_matiere", nullable: true)]
    private ?Matiere $matiere = null;
    

    #[ORM\Column(type: 'text')]
    // #[Assert\NotBlank(message: "Description is required")]
    #[Assert\Length(min: 5, minMessage: "Description must be at least 5 characters")]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'seance', targetEntity: Planning::class, orphanRemoval: true)]
private Collection $plannings;
    

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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getTypeSeance(): ?TypeSeance
    {
        return $this->typeSeance;
    }

    public function setTypeSeance(?TypeSeance $typeSeance): self
    {
        $this->typeSeance = $typeSeance;
        return $this;
    }

    public function getMatiere(): ?Matiere
    {
        return $this->matiere;
    }

    public function setMatiere(?Matiere $matiere): self
    {
        $this->matiere = $matiere;
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

    public function getPlanning(): ?Planning
    {
        return $this->planning;
    }

    public function setPlanning(?Planning $planning): self
    {
        $this->planning = $planning;
        return $this;
    }
    public function __construct()
{
    $this->plannings = new ArrayCollection();
}
public function getPlannings(): Collection
{
    return $this->plannings;
}


    public function __toString(): string
{
    // Vérifier que l'entité est initialisée
    if ($this->id === null) {
        return 'Nouvelle séance';
    }
    
    // Retourner le titre s'il existe
    if ($this->titre) {
        return $this->titre;
    }
    
    // Fallback
    return 'Séance #' . $this->id;
}
}