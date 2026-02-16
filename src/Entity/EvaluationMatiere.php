<?php

namespace App\Entity;

use App\Repository\EvaluationMatiereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationMatiereRepository::class)]
#[ORM\Table(name: 'evaluation_matiere')]
#[ORM\HasLifecycleCallbacks]
class EvaluationMatiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_eval')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'score_eval')]
    private ?float $scoreEval = null;

    #[ORM\Column(name: 'note_maximale_eval')]
    private ?float $noteMaximaleEval = null;

    #[ORM\Column(name: 'date_evaluation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateEvaluation = null;

    #[ORM\Column(name: 'duree_evaluation')]
    private ?int $dureeEvaluation = null;

    #[ORM\Column(name: 'priorite_e', length: 50, nullable: true)]
    private ?string $prioriteE = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: EvalMat::class, mappedBy: 'evaluation', orphanRemoval: true)]
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

    public function getScoreEval(): ?float
    {
        return $this->scoreEval;
    }

    public function setScoreEval(float $scoreEval): self
    {
        $this->scoreEval = $scoreEval;
        return $this;
    }

    public function getNoteMaximaleEval(): ?float
    {
        return $this->noteMaximaleEval;
    }

    public function setNoteMaximaleEval(float $noteMaximaleEval): self
    {
        $this->noteMaximaleEval = $noteMaximaleEval;
        return $this;
    }

    public function getDateEvaluation(): ?\DateTimeInterface
    {
        return $this->dateEvaluation;
    }

    public function setDateEvaluation(\DateTimeInterface $dateEvaluation): self
    {
        $this->dateEvaluation = $dateEvaluation;
        return $this;
    }

    public function getDureeEvaluation(): ?int
    {
        return $this->dureeEvaluation;
    }

    public function setDureeEvaluation(int $dureeEvaluation): self
    {
        $this->dureeEvaluation = $dureeEvaluation;
        return $this;
    }

    public function getPrioriteE(): ?string
    {
        return $this->prioriteE;
    }

    public function setPrioriteE(?string $prioriteE): self
    {
        $this->prioriteE = $prioriteE;
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
            $evalMat->setEvaluation($this);
        }

        return $this;
    }

    public function removeEvalMat(EvalMat $evalMat): self
    {
        if ($this->evalMats->removeElement($evalMat)) {
            if ($evalMat->getEvaluation() === $this) {
                $evalMat->setEvaluation(null);
            }
        }

        return $this;
    }

    /**
     * Calculate percentage score
     */
    public function getPercentage(): ?float
    {
        if ($this->noteMaximaleEval && $this->noteMaximaleEval > 0) {
            return ($this->scoreEval / $this->noteMaximaleEval) * 100;
        }
        return null;
    }
}
