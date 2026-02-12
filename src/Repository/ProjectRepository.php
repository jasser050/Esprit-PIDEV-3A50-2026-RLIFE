<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Find all projects for a specific user
     *
     * @param User $user
     * @return Project[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find projects by status for a user
     *
     * @param User $user
     * @param string $statut
     * @return Project[]
     */
    public function findByUserAndStatus(User $user, string $statut): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', $statut)
            ->orderBy('p.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total projects for a user
     *
     * @param User $user
     * @return int
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find upcoming projects (not yet completed)
     *
     * @param User $user
     * @return Project[]
     */
    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut != :statut')
            ->andWhere('p.dateFin >= :today')
            ->setParameter('user', $user)
            ->setParameter('statut', 'Terminé')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('p.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
