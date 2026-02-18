<?php

namespace App\Repository;

use App\Entity\StudentGamification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentGamification>
 */
class StudentGamificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentGamification::class);
    }

    public function findByUser(int $userId): ?StudentGamification
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findTopUsersByPoints(int $limit = 10): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.points', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopUsersByStreak(int $limit = 10): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.streak', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findUsersWithBadges(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.badges IS NOT NULL')
            ->andWhere('g.badges != :empty')
            ->setParameter('empty', '[]')
            ->orderBy('g.points', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
