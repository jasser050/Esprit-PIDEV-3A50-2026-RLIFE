<?php

namespace App\Repository;

use App\Entity\Deck;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * Trouve la note d'un utilisateur pour un deck donné
     */
    public function findByUserAndDeck(User $user, Deck $deck): ?Rating
    {
        return $this->findOneBy([
            'user' => $user,
            'deck' => $deck,
        ]);
    }

    /**
     * Statistiques globales pour un deck (moyenne + total)
     */
    public function getStatsForDeck(Deck $deck): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.stars) as average, COUNT(r.id) as total')
            ->where('r.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleResult();

        return [
            'average' => $result['average'] ? round((float)$result['average'], 1) : 0,
            'total'   => (int)$result['total'],
        ];
    }

    /**
     * Distribution des notes (nombre de votes par étoile : 1 à 5)
     * Retourne [ 1 => count, 2 => count, ..., 5 => count ]
     */
    public function getDistributionForDeck(Deck $deck): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.stars as stars, COUNT(r.id) as cnt')
            ->where('r.deck = :deck')
            ->setParameter('deck', $deck)
            ->groupBy('r.stars')
            ->getQuery()
            ->getArrayResult();

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($rows as $row) {
            $distribution[(int)$row['stars']] = (int)$row['cnt'];
        }

        return $distribution;
    }

    /**
     * Statistiques des critères avancés pour un deck
     * Retourne la moyenne de clarity, completeness, difficulty, usefulness
     */
    public function getCriteriaStatsForDeck(Deck $deck): array
    {
        $result = $this->createQueryBuilder('r')
            ->select(
                'AVG(r.clarity)      as clarity,
                 AVG(r.completeness) as completeness,
                 AVG(r.difficulty)   as difficulty,
                 AVG(r.usefulness)   as usefulness,
                 COUNT(r.clarity)    as clarity_count'
            )
            ->where('r.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleResult();

        return [
            'clarity'      => $result['clarity']      ? round((float)$result['clarity'], 1)      : null,
            'completeness' => $result['completeness'] ? round((float)$result['completeness'], 1) : null,
            'difficulty'   => $result['difficulty']   ? round((float)$result['difficulty'], 1)   : null,
            'usefulness'   => $result['usefulness']   ? round((float)$result['usefulness'], 1)   : null,
            'count'        => (int)$result['clarity_count'],
        ];
    }

    /**
     * Statistiques des tags pour un deck
     * Retourne un tableau trié par fréquence : [ ['tag' => '...', 'count' => N], ... ]
     */
    public function getTagStatsForDeck(Deck $deck): array
    {
        $ratings = $this->createQueryBuilder('r')
            ->select('r.tags')
            ->where('r.deck = :deck')
            ->andWhere('r.tags IS NOT NULL')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getArrayResult();

        $tagCounts = [];
        foreach ($ratings as $row) {
            $tags = $row['tags'];
            if (is_array($tags)) {
                foreach ($tags as $tag) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }

        arsort($tagCounts);

        $result = [];
        foreach ($tagCounts as $tag => $count) {
            $result[] = ['tag' => $tag, 'count' => $count];
        }

        return $result;
    }

    /**
     * Moyenne globale pour un deck
     */
    public function getAverageForDeck(Deck $deck): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.stars) as avg')
            ->where('r.deck = :deck')
            ->setParameter('deck', $deck)
            ->getQuery()
            ->getSingleResult();

        return $result['avg'] ? round((float)$result['avg'], 2) : 0.0;
    }

    /**
     * Top decks les mieux notés
     */
    public function getTopRatedDecks(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->select('IDENTITY(r.deck) as deck_id, AVG(r.stars) as avg_stars, COUNT(r.id) as total')
            ->groupBy('r.deck')
            ->having('COUNT(r.id) >= 1')
            ->orderBy('avg_stars', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Stats globales pour le dashboard admin
     */
    public function getGlobalStats(): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.stars) as average, COUNT(r.id) as total')
            ->getQuery()
            ->getSingleResult();

        return [
            'average' => $result['average'] ? round((float)$result['average'], 1) : 0,
            'total'   => (int)$result['total'],
        ];
    }
}
