<?php

namespace App\Repository;

use App\Entity\Pet;
use App\Entity\PetEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PetEvent>
 */
class PetEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PetEvent::class);
    }

    /**
     * @return PetEvent[]
     */
    public function findRecentByPet(Pet $pet, int $limit = 10): array
    {
        return $this->createQueryBuilder('pe')
            ->andWhere('pe.pet = :pet')
            ->setParameter('pet', $pet)
            ->orderBy('pe.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }
}

