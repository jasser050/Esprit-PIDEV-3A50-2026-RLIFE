<?php

namespace App\Repository;

use App\Entity\StudentGamification;
use App\Entity\User;
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

    /**
     * Retourne le top N des étudiants classés par points décroissants
     */
    public function findLeaderboard(int $limit = 10): array
    {
        return $this->createQueryBuilder('sg')
            ->leftJoin('sg.user', 'u')
            ->addSelect('u')
            ->orderBy('sg.points', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le rang d'un utilisateur (position dans le classement)
     */
    public function getUserRank(User $user): int
    {
        $result = $this->createQueryBuilder('sg')
            ->select('sg.points')
            ->where('sg.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result) {
            return 0;
        }

        $userPoints = $result['points'];

        $rank = $this->createQueryBuilder('sg')
            ->select('COUNT(sg.id)')
            ->where('sg.points > :points')
            ->setParameter('points', $userPoints)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $rank + 1;
    }

    /**
     * Trouve la gamification d'un user (alias pratique)
     */
    public function findByUser(User $user): ?StudentGamification
    {
        return $this->findOneBy(['user' => $user]);
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
