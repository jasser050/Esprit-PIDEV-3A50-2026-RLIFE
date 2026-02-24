<?php

namespace App\Controller;

use App\Service\StudyCoachService;
use App\Service\StreakService;
use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai-coach')]
#[IsGranted('ROLE_USER')]
class AICoachController extends AbstractController
{
    public function __construct(
        private StudyCoachService $studyCoachService,
        private StreakService      $streakService,
        private StatisticsService  $statisticsService,
    ) {}

    #[Route('/', name: 'app_ai_coach')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // ✅ Avant : $user->getAIRecommendations()
        $recommendations = $this->studyCoachService->getAIRecommendations($user);

        return $this->render('pages/ai_coach/dashboard.html.twig', [
            'recommendations' => $recommendations,
            'user_stats' => [
                // ✅ Avant : $user->getEvaluations()->count()
                'total_evaluations' => $user->getEvaluations()->count(),

                // ✅ Avant : $user->calculateOverallAverage()
                'overall_average'   => $this->statisticsService->calculateOverallAverage($user),

                // ✅ Avant : $user->getHighScoreStreak()['current']
                'current_streak'    => $this->streakService->getHighScoreStreak($user)['current'],
            ],
        ]);
    }

    #[Route('/api/recommendations', name: 'app_ai_coach_api')]
    public function apiRecommendations(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        // ✅ Avant : $user->getAIRecommendations()
        $recommendations = $this->studyCoachService->getAIRecommendations($user);

        return $this->json($recommendations);
    }

    #[Route('/study-schedule', name: 'app_ai_coach_schedule')]
    public function studySchedule(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // ✅ Avant : $user->getAIRecommendations()
        $recommendations = $this->studyCoachService->getAIRecommendations($user);

        return $this->render('pages/ai_coach/schedule.html.twig', [
            'schedule' => $recommendations['study_schedule'],
            'at_risk'  => $recommendations['at_risk_subjects'],
        ]);
    }
}