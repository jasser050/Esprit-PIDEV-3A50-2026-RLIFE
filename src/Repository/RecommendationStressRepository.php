<?php

namespace App\Repository;

use App\Entity\RecommendationStress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecommendationStressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecommendationStress::class);
    }

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
    }
}
