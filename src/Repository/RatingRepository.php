<?php

namespace App\Repository;

use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function findByDeck(int $deckId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.deck = :deckId')
            ->setParameter('deckId', $deckId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserRatingForDeck(int $userId, int $deckId): ?Rating
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->andWhere('r.deck = :deckId')
            ->setParameter('userId', $userId)
            ->setParameter('deckId', $deckId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getAverageRatingForDeck(int $deckId): float|int
    {
        return $this->createQueryBuilder('r')
            ->select('AVG(r.stars)')
            ->andWhere('r.deck = :deckId')
            ->setParameter('deckId', $deckId)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
}
