<?php

namespace App\Controller;

use App\Entity\StudentGamification;
use App\Service\GamificationService;
use App\Repository\StudentGamificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gamification')]
class GamificationController extends AbstractController
{
    public function __construct(
        private GamificationService $gamificationService,
        private StudentGamificationRepository $gamificationRepository,
        private EntityManagerInterface $em
    ) {}

    // ===================== DASHBOARD ÉTUDIANT =====================

    #[Route('/dashboard', name: 'gamification_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        // Récupérer ou créer le profil de gamification
        $gamification = $this->gamificationRepository->findOneBy(['user' => $user]);

        if (!$gamification) {
            $gamification = new StudentGamification();
            $gamification->setUser($user);
            $this->em->persist($gamification);
            $this->em->flush();
        }

        // Mettre à jour le streak au chargement du dashboard
        $this->gamificationService->updateStreak($gamification);

        // Classement top 10
        $leaderboard = $this->gamificationRepository->findLeaderboard(10);

        // Rang de l'utilisateur actuel
        $userRank = $this->gamificationRepository->getUserRank($user);

        // XP pour passer au niveau suivant
        $nextLevelXp = $gamification->getLevel() * 100;
        $currentLevelXp = ($gamification->getLevel() - 1) * 100;
        $progressPercent = 0;

        if ($nextLevelXp > $currentLevelXp) {
            $progressPercent = (int)(
                (($gamification->getPoints() - $currentLevelXp) / ($nextLevelXp - $currentLevelXp)) * 100
            );
        }

        return $this->render('pages/revisions/gamification.html.twig', [
            'gamification'   => $gamification,
            'leaderboard'    => $leaderboard,
            'userRank'       => $userRank,
            'nextLevelXp'    => $nextLevelXp,
            'progressPercent'=> min(100, $progressPercent),
        ]);
    }

    // ===================== BONNE RÉPONSE =====================

    #[Route('/correct-answer', name: 'gamification_correct_answer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function correctAnswer(): Response
    {
        $user = $this->getUser();
        $gamification = $this->gamificationRepository->findOneBy(['user' => $user]);

        if (!$gamification) {
            $gamification = new StudentGamification();
            $gamification->setUser($user);
            $this->em->persist($gamification);
        }

        // +10 XP pour bonne réponse
        $this->gamificationService->addPoints($gamification, 10);

        // Incrémenter total bonnes réponses
        $gamification->setTotalCorrectAnswers($gamification->getTotalCorrectAnswers() + 1);

        // Vérifier badges liés aux bonnes réponses
        $this->gamificationService->checkAnswerBadges($gamification);

        // Mettre à jour le streak
        $this->gamificationService->updateStreak($gamification);

        $this->em->flush();

        return $this->json([
            'success' => true,
            'points'  => $gamification->getPoints(),
            'level'   => $gamification->getLevel(),
            'streak'  => $gamification->getStreak(),
            'badges'  => $gamification->getBadges(),
            'message' => '+10 XP ! Bonne réponse 🎯',
        ]);
    }

    // ===================== DECK COMPLÉTÉ =====================

    #[Route('/deck-completed/{deckId}', name: 'gamification_deck_completed', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deckCompleted(int $deckId): Response
    {
        $user = $this->getUser();
        $gamification = $this->gamificationRepository->findOneBy(['user' => $user]);

        if (!$gamification) {
            $gamification = new StudentGamification();
            $gamification->setUser($user);
            $this->em->persist($gamification);
        }

        // +50 XP pour deck terminé
        $this->gamificationService->addPoints($gamification, 50);

        // Incrémenter total decks complétés
        $gamification->setTotalDecksCompleted($gamification->getTotalDecksCompleted() + 1);

        // Vérifier badge "Master Deck"
        $newBadges = $this->gamificationService->checkDeckBadges($gamification);

        // Mettre à jour le streak
        $this->gamificationService->updateStreak($gamification);

        $this->em->flush();

        return $this->json([
            'success'   => true,
            'points'    => $gamification->getPoints(),
            'level'     => $gamification->getLevel(),
            'streak'    => $gamification->getStreak(),
            'badges'    => $gamification->getBadges(),
            'newBadges' => $newBadges,
            'message'   => '+50 XP ! Deck terminé 🏆',
        ]);
    }

    // ===================== CLASSEMENT =====================

    #[Route('/leaderboard', name: 'gamification_leaderboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function leaderboard(): Response
    {
        $leaderboard = $this->gamificationRepository->findLeaderboard(10);
        $userRank    = $this->gamificationRepository->getUserRank($this->getUser());

        return $this->render('gamification/leaderboard.html.twig', [
            'leaderboard' => $leaderboard,
            'userRank'    => $userRank,
        ]);
    }

    // ===================== BADGES =====================

    #[Route('/badges', name: 'gamification_badges', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function badges(): Response
    {
        $user         = $this->getUser();
        $gamification = $this->gamificationRepository->findOneBy(['user' => $user]);

        $allBadges = [
            ['name' => 'Master Deck',         'icon' => '🎖',  'description' => 'Terminer un deck à 100%'],
            ['name' => 'Streak 7 jours',      'icon' => '🔥',  'description' => 'Réviser 7 jours consécutifs'],
            ['name' => '100 Bonnes Réponses', 'icon' => '💯',  'description' => 'Donner 100 bonnes réponses'],
            ['name' => '500 Bonnes Réponses', 'icon' => '🚀',  'description' => 'Donner 500 bonnes réponses'],
            ['name' => 'Niveau 5',            'icon' => '⭐',  'description' => 'Atteindre le niveau 5'],
            ['name' => 'Niveau 10',           'icon' => '🌟',  'description' => 'Atteindre le niveau 10'],
        ];

        $earnedNames = $gamification
            ? array_column($gamification->getBadges() ?? [], 'name')
            : [];

        foreach ($allBadges as &$badge) {
            $badge['earned'] = in_array($badge['name'], $earnedNames);
        }

        return $this->render('gamification/badges.html.twig', [
            'allBadges'    => $allBadges,
            'gamification' => $gamification,
        ]);
    }

    // ===================== RÉVISION QUOTIDIENNE =====================

    #[Route('/daily-revision', name: 'gamification_daily_revision', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function dailyRevision(): Response
    {
        $user         = $this->getUser();
        $gamification = $this->gamificationRepository->findOneBy(['user' => $user]);

        if (!$gamification) {
            $gamification = new StudentGamification();
            $gamification->setUser($user);
            $this->em->persist($gamification);
        }

        $today       = new \DateTime('today');
        $lastActivity = $gamification->getLastActivity();

        // Vérifier si déjà récompensé aujourd'hui
        if ($lastActivity && $lastActivity->format('Y-m-d') === $today->format('Y-m-d')) {
            return $this->json([
                'success' => false,
                'message' => 'Déjà récompensé aujourd\'hui ! Reviens demain 😊',
            ]);
        }

        // +15 XP révision quotidienne
        $this->gamificationService->addPoints($gamification, 15);
        $this->gamificationService->updateStreak($gamification);

        // Vérifier badge streak 7 jours
        $newBadges = $this->gamificationService->checkStreakBadges($gamification);

        $this->em->flush();

        return $this->json([
            'success'   => true,
            'points'    => $gamification->getPoints(),
            'level'     => $gamification->getLevel(),
            'streak'    => $gamification->getStreak(),
            'newBadges' => $newBadges,
            'message'   => '+15 XP ! Révision quotidienne 📚',
        ]);
    }
}