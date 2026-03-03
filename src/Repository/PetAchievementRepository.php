<?php

namespace App\Repository;

use App\Entity\Pet;
use App\Entity\PetAchievement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PetAchievement>
 */
class PetAchievementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PetAchievement::class);
    }

    public function hasCode(Pet $pet, string $code): bool
    {
        $count = (int) $this->createQueryBuilder('pa')
            ->select('COUNT(pa.id)')
            ->andWhere('pa.pet = :pet')
            ->andWhere('pa.code = :code')
            ->setParameter('pet', $pet)
            ->setParameter('code', trim($code))
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @return PetAchievement[]
     */
    public function findByPet(Pet $pet): array
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.pet = :pet')
            ->setParameter('pet', $pet)
            ->orderBy('pa.unlockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

