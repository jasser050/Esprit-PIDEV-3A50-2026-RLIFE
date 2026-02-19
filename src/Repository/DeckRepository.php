<?php

namespace App\Repository;

use App\Entity\Deck;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deck>
 */
class DeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deck::class);
    }

    /**
     * Find all decks for a specific user
     *
     * @param User $user
     * @return Deck[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find decks by subject (matiere) for a user
     *
     * @param User $user
     * @param string $matiere
     * @return Deck[]
     */
    public function findByUserAndSubject(User $user, string $matiere): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.matiere = :matiere')
            ->setParameter('user', $user)
            ->setParameter('matiere', $matiere)
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find decks by level (niveau) for a user
     *
     * @param User $user
     * @param string $niveau
     * @return Deck[]
     */
    public function findByUserAndLevel(User $user, string $niveau): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.niveau = :niveau')
            ->setParameter('user', $user)
            ->setParameter('niveau', $niveau)
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total decks for a user
     *
     * @param User $user
     * @return int
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.idDeck)')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Search decks by title for a user
     *
     * @param User $user
     * @param string $searchTerm
     * @return Deck[]
     */
    public function searchByUserAndTitle(User $user, string $searchTerm): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.titre LIKE :search')
            ->setParameter('user', $user)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
