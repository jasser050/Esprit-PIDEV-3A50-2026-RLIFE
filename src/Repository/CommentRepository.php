<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Assignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Find all comments for a specific assignment
     *
     * @param Assignment $assignment
     * @return Comment[]
     */
    public function findByAssignment(Assignment $assignment): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.assignment = :assignment')
            ->setParameter('assignment', $assignment)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count comments for an assignment
     *
     * @param Assignment $assignment
     * @return int
     */
    public function countByAssignment(Assignment $assignment): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.assignment = :assignment')
            ->setParameter('assignment', $assignment)
            ->getQuery()
            ->getSingleScalarResult();
    }
}