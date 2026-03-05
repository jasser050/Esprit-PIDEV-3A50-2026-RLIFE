<?php
// src/Repository/UserFavoriteTeamRepository.php

namespace App\Repository;

use App\Entity\UserFavoriteTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFavoriteTeam>
 */
class UserFavoriteTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFavoriteTeam::class);
    }

    /**
     * Trouver l'équipe favorite d'un utilisateur
     */
    public function findByUser($user): ?UserFavoriteTeam
    {
        return $this->createQueryBuilder('uft')
            ->andWhere('uft.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Sauvegarder l'équipe favorite
     */
    public function save(UserFavoriteTeam $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprimer l'équipe favorite
     */
    public function remove(UserFavoriteTeam $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}