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

    public function findByFlashcard(int $flashcardId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.flashcard = :flashcardId')
            ->setParameter('flashcardId', $flashcardId)
            ->orderBy('t.language', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByLanguage(string $language): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.language = :language')
            ->setParameter('language', $language)
            ->getQuery()
            ->getResult();
    }

    public function findVerifiedTranslations(int $flashcardId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.flashcard = :flashcardId')
            ->andWhere('t.isVerified = :verified')
            ->setParameter('flashcardId', $flashcardId)
            ->setParameter('verified', true)
            ->getQuery()
            ->getResult();
    }
}
