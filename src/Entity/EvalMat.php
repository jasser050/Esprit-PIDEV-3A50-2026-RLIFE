<?php

namespace App\Entity;

use App\Repository\EvalMatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvalMatRepository::class)]
#[ORM\Table(name: 'eval_mat')]
class EvalMat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Matiere::class, inversedBy: 'evalMats')]
    #[ORM\JoinColumn(name: 'matiere_id', referencedColumnName: 'id_matiere', nullable: false, onDelete: 'CASCADE')]
    private ?Matiere $matiere = null;

    #[ORM\ManyToOne(targetEntity: EvaluationMatiere::class, inversedBy: 'evalMats')]
    #[ORM\JoinColumn(name: 'evaluation_id', referencedColumnName: 'id_eval', nullable: false, onDelete: 'CASCADE')]
    private ?EvaluationMatiere $evaluation = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEvaluation(): ?EvaluationMatiere
    {
        return $this->evaluation;
    }

    public function setEvaluation(?EvaluationMatiere $evaluation): self
    {
        $this->evaluation = $evaluation;
        return $this;
    }
}
