<?php

namespace App\Repository;

use App\Entity\FlashcardTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FlashcardTranslation>
 */
class FlashcardTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FlashcardTranslation::class);
    }

    /**
     * Trouver toutes les traductions d'une flashcard
     */
    public function findByFlashcard(int $flashcardId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.flashcard = :flashcardId')
            ->setParameter('flashcardId', $flashcardId)
            ->orderBy('t.language', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver une traduction spécifique par flashcard et langue
     */
    public function findOneByFlashcardAndLanguage(int $flashcardId, string $language): ?FlashcardTranslation
    {
        return $this->createQueryBuilder('t')
            ->where('t.flashcard = :flashcardId')
            ->andWhere('t.language = :language')
            ->setParameter('flashcardId', $flashcardId)
            ->setParameter('language', $language)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compter le nombre de traductions par langue pour un deck
     */
    public function countByDeckAndLanguage(int $deckId): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.language', 'COUNT(t.id) as count')
            ->join('t.flashcard', 'f')
            ->join('f.deck', 'd')
            ->where('d = :deckId')
            ->setParameter('deckId', $deckId)
            ->groupBy('t.language')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les traductions vérifiées d'une flashcard
     */
    public function findVerified(int $flashcardId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.flashcard = :flashcardId')
            ->andWhere('t.isVerified = true')
            ->setParameter('flashcardId', $flashcardId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtenir les statistiques globales de traduction.
     *
     * FIX: chaque stat utilise son propre QueryBuilder pour éviter
     * la réutilisation d'un QB déjà exécuté (bug original).
     */
    public function getGlobalStats(): array
    {
        $total = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $languages = $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.language)')
            ->getQuery()
            ->getSingleScalarResult();

        $verified = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.isVerified = true')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_translations' => (int) $total,
            'languages_count'    => (int) $languages,
            'verified_count'     => (int) $verified,
        ];
    }
}