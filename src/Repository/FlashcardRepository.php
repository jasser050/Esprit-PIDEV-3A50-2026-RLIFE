<?php

namespace App\Repository;

use App\Entity\Flashcard;
use App\Entity\Deck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Flashcard>
 */
class FlashcardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Flashcard::class);
    }

    /**
     * Find all flashcards for a specific deck
     *
     * @param Deck $deck
     * @return Flashcard[]
     */
    public function findByDeck(Deck $deck): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.deck = :deck')
            ->setParameter('deck', $deck)
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find flashcards by difficulty level
     *
     * @param Deck $deck
     * @param int $niveauDifficulte
     * @return Flashcard[]
     */
    public function findByDeckAndDifficulty(Deck $deck, int $niveauDifficulte): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.deck = :deck')
            ->andWhere('f.niveauDifficulte = :niveau')
            ->setParameter('deck', $deck)
            ->setParameter('niveau', $niveauDifficulte)
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find flashcards by state (etat)
     *
     * @param Deck $deck
     * @param string $etat
     * @return Flashcard[]
     */
    public function findByDeckAndState(Deck $deck, string $etat): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.deck = :deck')
            ->andWhere('f.etat = :etat')
            ->setParameter('deck', $deck)
            ->setParameter('etat', $etat)
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total flashcards in a deck
     *
     * @param Deck $deck
     * @return int
     */
    public function countByDeck(Deck $deck): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Search flashcards by question or answer
     *
     * @param Deck $deck
     * @param string $searchTerm
     * @return Flashcard[]
     */
    public function searchByDeck(Deck $deck, string $searchTerm): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.deck = :deck')
            ->andWhere('f.question LIKE :search OR f.reponse LIKE :search OR f.titre LIKE :search')
            ->setParameter('deck', $deck)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get flashcards for revision (recently modified or new)
     *
     * @param Deck $deck
     * @param int $limit
     * @return Flashcard[]
     */
    public function findRecentlyModifiedByDeck(Deck $deck, int $limit = 10): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.deck = :deck')
            ->setParameter('deck', $deck)
            ->orderBy('f.dateModification', 'DESC')
            ->addOrderBy('f.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
