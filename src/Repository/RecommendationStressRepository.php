<?php

namespace App\Repository;

use App\Entity\RecommendationStress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecommendationStress>
 */
class RecommendationStressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecommendationStress::class);
    }

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
    }
}