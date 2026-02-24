<?php

namespace App\Repository;

use App\Entity\EvalMat;
use App\Entity\Matiere;
use App\Entity\EvaluationMatiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvalMat>
 */
class EvalMatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvalMat::class);
    }

    /**
     * Find all evaluations for a specific matiere
     * @return EvalMat[]
     */
    public function findByMatiere(Matiere $matiere): array
    {
        return $this->createQueryBuilder('em')
            ->andWhere('em.matiere = :matiere')
            ->setParameter('matiere', $matiere)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all matieres for a specific evaluation
     * @return EvalMat[]
     */
    public function findByEvaluation(EvaluationMatiere $evaluation): array
    {
        return $this->createQueryBuilder('em')
            ->andWhere('em.evaluation = :evaluation')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a matiere-evaluation link exists
     */
    public function exists(Matiere $matiere, EvaluationMatiere $evaluation): bool
    {
        $result = $this->createQueryBuilder('em')
            ->select('COUNT(em.id)')
            ->andWhere('em.matiere = :matiere')
            ->andWhere('em.evaluation = :evaluation')
            ->setParameter('matiere', $matiere)
            ->setParameter('evaluation', $evaluation)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
