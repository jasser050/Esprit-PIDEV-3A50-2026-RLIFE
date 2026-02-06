<?php

namespace App\Repository;

use App\Entity\Planning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Planning>
 */
class PlanningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planning::class);
    }

    /**
     * Find all plannings for a specific user
     * @return Planning[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find plannings within a date range
     * @return Planning[]
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end, int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :userId')
            ->andWhere('p.dateDebut >= :start')
            ->andWhere('p.dateFin <= :end')
            ->setParameter('userId', $userId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming plannings for a user
     * @return Planning[]
     */
    public function findUpcoming(int $userId, int $limit = 10): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :userId')
            ->andWhere('p.dateDebut >= :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', $now)
            ->orderBy('p.dateDebut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
