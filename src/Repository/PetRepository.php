<?php

namespace App\Repository;

use App\Entity\Pet;
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
}