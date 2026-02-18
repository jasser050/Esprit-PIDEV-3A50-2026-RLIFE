<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Repository\DeckRepository;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rating')]
class RatingController extends AbstractController
{
    // ══════════════════════════════════════════
    //  PAGE DÉDIÉE — /rating/deck/{deckId}
    // ══════════════════════════════════════════

    #[Route('/deck/{deckId}', name: 'app_rating_page', methods: ['GET'])]
    public function ratePage(
        int $deckId,
        DeckRepository $deckRepo
    ): Response {
        $deck = $deckRepo->find($deckId);

        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

        return $this->render('admin/ratings/deck_rating.html.twig', [
            'deck' => $deck,
        ]);
    }

    // ══════════════════════════════════════════
    //  API — GET  /rating/deck/{deckId}/my-rating
    //  Retourne la note de l'utilisateur + moyenne + distribution
    // ══════════════════════════════════════════

    #[Route('/deck/{deckId}/my-rating', name: 'app_rating_get', methods: ['GET'])]
    public function getMyRating(
        int $deckId,
        RatingRepository $ratingRepo,
        DeckRepository $deckRepo
    ): JsonResponse {
        $deck = $deckRepo->find($deckId);
        if (!$deck) {
            return $this->json(['success' => false, 'message' => 'Deck not found'], 404);
        }

        $user      = $this->getUser();
        $userStars = null;
        $userCriteria = null;
        $userTags     = null;
        $userComment  = null;

        if ($user) {
            $existing = $ratingRepo->findByUserAndDeck($user, $deck);
            if ($existing) {
                $userStars    = $existing->getStars();
                $userCriteria = [
                    'clarity'      => $existing->getClarity(),
                    'completeness' => $existing->getCompleteness(),
                    'difficulty'   => $existing->getDifficulty(),
                    'usefulness'   => $existing->getUsefulness(),
                ];
                $userTags    = $existing->getTags();
                $userComment = $existing->getComment();
            }
        }

        $stats        = $ratingRepo->getStatsForDeck($deck);
        $distribution = $ratingRepo->getDistributionForDeck($deck);

        return $this->json([
            'success'      => true,
            'userStars'    => $userStars,
            'userCriteria' => $userCriteria,
            'userTags'     => $userTags,
            'userComment'  => $userComment,
            'average'      => $stats['average'] ?? 0,
            'total'        => $stats['total']   ?? 0,
            'distribution' => $distribution,
        ]);
    }

    // ══════════════════════════════════════════
    //  API — POST /rating/deck/{deckId}/rate
    //  Soumet ou modifie la note
    // ══════════════════════════════════════════

    #[Route('/deck/{deckId}/rate', name: 'app_rating_submit', methods: ['POST'])]
    public function submitRating(
        int $deckId,
        Request $request,
        DeckRepository $deckRepo,
        RatingRepository $ratingRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Please log in to rate'], 401);
        }

        $deck = $deckRepo->find($deckId);
        if (!$deck) {
            return $this->json(['success' => false, 'message' => 'Deck not found'], 404);
        }

        $data  = json_decode($request->getContent(), true) ?? [];
        $stars = (int) ($data['stars'] ?? 0);

        if ($stars < 1 || $stars > 5) {
            return $this->json(['success' => false, 'message' => 'Invalid rating (1-5)'], 400);
        }

        // ── Champs optionnels ──
        $comment  = isset($data['comment']) ? substr(trim($data['comment']), 0, 500) : null;
        $tags     = isset($data['tags']) && is_array($data['tags']) ? array_slice($data['tags'], 0, 10) : null;
        $criteria = isset($data['criteria']) && is_array($data['criteria']) ? $data['criteria'] : [];

        $clarity      = isset($criteria['clarity'])      && $criteria['clarity']      >= 1 ? (int)$criteria['clarity']      : null;
        $completeness = isset($criteria['completeness']) && $criteria['completeness'] >= 1 ? (int)$criteria['completeness'] : null;
        $difficulty   = isset($criteria['difficulty'])   && $criteria['difficulty']   >= 1 ? (int)$criteria['difficulty']   : null;
        $usefulness   = isset($criteria['usefulness'])   && $criteria['usefulness']   >= 1 ? (int)$criteria['usefulness']   : null;

        $user     = $this->getUser();
        $existing = $ratingRepo->findByUserAndDeck($user, $deck);
        $isNew    = false;

        if ($existing) {
            $existing->setStars($stars);
            $existing->setUpdatedAt(new \DateTimeImmutable());
        } else {
            $isNew    = true;
            $existing = new Rating();
            $existing->setUser($user);
            $existing->setDeck($deck);
            $existing->setCreatedAt(new \DateTimeImmutable());
            $existing->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($existing);
        }

        // ── Mise à jour des champs ──
        $existing->setStars($stars);
        $existing->setComment($comment ?: null);
        $existing->setTags(!empty($tags) ? $tags : null);
        $existing->setClarity($clarity);
        $existing->setCompleteness($completeness);
        $existing->setDifficulty($difficulty);
        $existing->setUsefulness($usefulness);
        $existing->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        $stats        = $ratingRepo->getStatsForDeck($deck);
        $distribution = $ratingRepo->getDistributionForDeck($deck);

        return $this->json([
            'success'      => true,
            'message'      => $isNew ? 'Rating submitted!' : 'Rating updated!',
            'userStars'    => $stars,
            'average'      => $stats['average'] ?? 0,
            'total'        => $stats['total']   ?? 0,
            'distribution' => $distribution,
        ]);
    }

    // ══════════════════════════════════════════
    //  API — POST /rating/deck/{deckId}/remove
    //  Supprime la note
    // ══════════════════════════════════════════

    #[Route('/deck/{deckId}/remove', name: 'app_rating_remove', methods: ['POST'])]
    public function removeRating(
        int $deckId,
        DeckRepository $deckRepo,
        RatingRepository $ratingRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $deck = $deckRepo->find($deckId);
        if (!$deck) {
            return $this->json(['success' => false, 'message' => 'Deck not found'], 404);
        }

        $user     = $this->getUser();
        $existing = $ratingRepo->findByUserAndDeck($user, $deck);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $stats        = $ratingRepo->getStatsForDeck($deck);
        $distribution = $ratingRepo->getDistributionForDeck($deck);

        return $this->json([
            'success'      => true,
            'message'      => 'Rating deleted',
            'average'      => $stats['average'] ?? 0,
            'total'        => $stats['total']   ?? 0,
            'distribution' => $distribution,
        ]);
    }
}