<?php

namespace App\Repository;

use App\Entity\Pet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pet::class);
    }

    // Méthode utile si besoin plus tard
    public function findMainPetByUser(User $user): ?Pet
    {
        return $this->findOneBy(['user' => $user], ['createdAt' => 'ASC']);
    }

    /**
     * @return Pet[]
     */
    public function findTopPetsByPower(int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.level', 'DESC')
            ->addOrderBy('p.xp', 'DESC')
            ->addOrderBy('p.health', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }
}
