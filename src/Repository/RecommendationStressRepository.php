<?php

namespace App\Repository;

use App\Entity\RecommendationStress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

<<<<<<< HEAD
/**
 * @extends ServiceEntityRepository<RecommendationStress>
 */
=======
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
class RecommendationStressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecommendationStress::class);
    }

<<<<<<< HEAD
    public function findByLevel(string $level): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.level = :level')
            ->andWhere('r.isActive = :active')
            ->setParameter('level', $level)
            ->setParameter('active', true)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.level', 'ASC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
=======
    public function findBestForScore(int $score): ?RecommendationStress
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = 1')
            ->andWhere(':score BETWEEN r.minScore AND r.maxScore')
            ->setParameter('score', $score)
            ->orderBy('r.minScore', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
    }
}
