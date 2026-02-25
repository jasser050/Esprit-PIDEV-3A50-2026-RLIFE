<?php

namespace App\Repository;

use App\Entity\EvaluationMatiere;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationMatiere>
 */
class EvaluationMatiereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationMatiere::class);
    }

    /**
     * Find all evaluations for a specific user
     * @return EvaluationMatiere[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.dateEvaluation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming evaluations for a user
     * @return EvaluationMatiere[]
     */
    public function findUpcomingByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->andWhere('e.dateEvaluation > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dateEvaluation', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find past evaluations for a user
     * @return EvaluationMatiere[]
     */
    public function findPastByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->andWhere('e.dateEvaluation <= :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dateEvaluation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate average score for a user
     */
    public function getAverageScore(User $user): ?float
    {
        $qb = $this->createQueryBuilder('e');
        
        $result = $qb->select('AVG((e.scoreEval / e.noteMaximaleEval) * 100)')
            ->andWhere('e.user = :user')
            ->andWhere('e.noteMaximaleEval > 0')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    /**
     * Count total evaluations for a user
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find evaluations by priority for a user
     * @return EvaluationMatiere[]
     */
    public function findByPriority(string $priority, User $user): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.priorite = :priority')
            ->andWhere('e.user = :user')
            ->setParameter('priority', $priority)
            ->setParameter('user', $user)
            ->orderBy('e.dateEvaluation', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
