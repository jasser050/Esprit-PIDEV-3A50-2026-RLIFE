<?php

namespace App\Repository;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assignment>
 */
class AssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

    /**
     * Find all assignments for a specific user
     *
     * @param User $user
     * @return Assignment[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assignments by project
     *
     * @param Project $project
     * @return Assignment[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.project = :project')
            ->setParameter('project', $project)
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assignments by priority for a user
     *
     * @param User $user
     * @param string $priorite
     * @return Assignment[]
     */
    public function findByUserAndPriority(User $user, string $priorite): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.priorite = :priorite')
            ->setParameter('user', $user)
            ->setParameter('priorite', $priorite)
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assignments by status for a user
     *
     * @param User $user
     * @param string $statut
     * @return Assignment[]
     */
    public function findByUserAndStatus(User $user, string $statut): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', $statut)
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total assignments for a user
     *
     * @param User $user
     * @return int
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find upcoming assignments (not yet completed)
     *
     * @param User $user
     * @return Assignment[]
     */
    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut != :statut')
            ->andWhere('a.dateFin >= :today')
            ->setParameter('user', $user)
            ->setParameter('statut', 'Terminé')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find overdue assignments
     *
     * @param User $user
     * @return Assignment[]
     */
    public function findOverdueByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut != :statut')
            ->andWhere('a.dateFin < :today')
            ->setParameter('user', $user)
            ->setParameter('statut', 'Terminé')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('a.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
