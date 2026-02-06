<?php

namespace App\Repository;

use App\Entity\Matiere;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Matiere>
 */
class MatiereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Matiere::class);
    }

    /**
     * Find all matieres for a specific user
     * @return Matiere[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.nomMatiere', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find matiere by code for a specific user
     */
    public function findByCodeAndUser(string $code, User $user): ?Matiere
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.code = :code')
            ->andWhere('m.user = :user')
            ->setParameter('code', $code)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find matieres by section for a specific user
     * @return Matiere[]
     */
    public function findBySection(string $section, User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.sectionMatiere = :section')
            ->andWhere('m.user = :user')
            ->setParameter('section', $section)
            ->setParameter('user', $user)
            ->orderBy('m.nomMatiere', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total matieres for a user
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
