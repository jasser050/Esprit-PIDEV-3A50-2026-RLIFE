<?php

namespace App\Controller\Admin;

use App\Repository\DeckRepository;
use App\Repository\RatingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ratings')]
#[IsGranted('ROLE_ADMIN')]
class AdminRatingController extends AbstractController
{
    // ══════════════════════════════════════════
    //  INDEX — /admin/ratings
    // ══════════════════════════════════════════

    #[Route('', name: 'app_admin_ratings', methods: ['GET'])]
    public function index(
        RatingRepository $ratingRepository,
        DeckRepository $deckRepository
    ): Response {
        $globalStats = $ratingRepository->getGlobalStats();
        $topDecks    = $ratingRepository->getTopRatedDecks(5);

        $totalVotesGlobal = array_sum(array_column($globalStats, 'total_votes'));

        $averageGlobal = 0;
        if (!empty($globalStats)) {
            $sum = array_sum(array_map(fn($s) => $s['average'] * $s['total_votes'], $globalStats));
            $averageGlobal = $totalVotesGlobal > 0
                ? round($sum / $totalVotesGlobal, 1)
                : 0;
        }

        return $this->render('admin/ratings/index.html.twig', [
            'globalStats'      => $globalStats,
            'topDecks'         => $topDecks,
            'totalVotesGlobal' => $totalVotesGlobal,
            'averageGlobal'    => $averageGlobal,
        ]);
    }

    // ══════════════════════════════════════════
    //  DÉTAIL DECK — /admin/ratings/deck/{deckId}
    // ══════════════════════════════════════════

    #[Route('/deck/{deckId}', name: 'app_admin_ratings_deck', methods: ['GET'])]
    public function deckStats(
        int $deckId,
        DeckRepository $deckRepository,
        RatingRepository $ratingRepository
    ): Response {
        $deck = $deckRepository->find($deckId);
        if (!$deck) {
            $this->addFlash('error', 'Deck introuvable.');
            return $this->redirectToRoute('app_admin_ratings');
        }

        $stats        = $ratingRepository->getStatsForDeck($deck);
        $distribution = $ratingRepository->getDistributionForDeck($deck);
        $ratings      = $ratingRepository->findBy(['deck' => $deck], ['createdAt' => 'DESC']);

        // ── Statistiques critères (moyenne de chaque critère) ──
        $criteriaStats = $ratingRepository->getCriteriaStatsForDeck($deck);

        // ── Top tags ──
        $tagStats = $ratingRepository->getTagStatsForDeck($deck);

        return $this->render('admin/ratings/deck_stats.html.twig', [
            'deck'          => $deck,
            'stats'         => array_merge($stats, ['distribution' => $distribution]),
            'ratings'       => $ratings,
            'criteriaStats' => $criteriaStats,
            'tagStats'      => $tagStats,
        ]);
    }

    // ══════════════════════════════════════════
    //  API CHART — /admin/ratings/api/chart-data
    // ══════════════════════════════════════════

    #[Route('/api/chart-data', name: 'app_admin_ratings_chart', methods: ['GET'])]
    public function chartData(RatingRepository $ratingRepository): JsonResponse
    {
        $globalStats = $ratingRepository->getGlobalStats();

        $labels   = [];
        $averages = [];
        $totals   = [];

        foreach (array_slice($globalStats, 0, 10) as $stat) {
            $labels[]   = mb_strlen($stat['titre']) > 20
                ? mb_substr($stat['titre'], 0, 20) . '…'
                : $stat['titre'];
            $averages[] = (float) $stat['average'];
            $totals[]   = (int) $stat['total_votes'];
        }

        return $this->json([
            'labels'   => $labels,
            'averages' => $averages,
            'totals'   => $totals,
        ]);
    }
}
