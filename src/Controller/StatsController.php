<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/stats')]
#[IsGranted('ROLE_USER')]
class StatsController extends AbstractController
{
    public function __construct(
        private StatisticsService $statisticsService,
    ) {}

    #[Route('/', name: 'app_stats_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/stats/dashboard.html.twig', [
            // ✅ Avant : $user->getStreakHistory(30)
            'history'             => $this->statisticsService->getStreakHistory($user, 30),

            // ✅ Avant : $user->getPerformanceByPeriod('month')
            'monthly_performance' => $this->statisticsService->getPerformanceByPeriod($user, 'month'),

            // ✅ Avant : $user->getPerformanceByPeriod('week')
            'weekly_performance'  => $this->statisticsService->getPerformanceByPeriod($user, 'week'),

            // ✅ Avant : $user->predictNextScore()
            'prediction'          => $this->statisticsService->predictNextScore($user),

            // ✅ Avant : $user->getCompleteStatistics()
            'complete_stats'      => $this->statisticsService->getCompleteStatistics($user),
        ]);
    }

    #[Route('/chart-data', name: 'app_stats_chart_data')]
    public function chartData(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        // ✅ Avant : $user->getStreakHistory(30)
        $history = $this->statisticsService->getStreakHistory($user, 30);

        return $this->json([
            'labels'      => array_column($history, 'date'),
            'streaks'     => array_column($history, 'streak'),
            'percentages' => array_column($history, 'percentage'),
        ]);
    }

    #[Route('/performance/{period}', name: 'app_stats_performance')]
    public function performance(string $period = 'month'): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $allowedPeriods = ['week', 'month', 'year'];
        if (!in_array($period, $allowedPeriods)) {
            return $this->json(['error' => 'Invalid period'], 400);
        }

        // ✅ Avant : $user->getPerformanceByPeriod($period)
        $performance = $this->statisticsService->getPerformanceByPeriod($user, $period);

        return $this->json($performance);
    }

    #[Route('/prediction', name: 'app_stats_prediction')]
    public function prediction(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        // ✅ Avant : $user->predictNextScore()
        $prediction = $this->statisticsService->predictNextScore($user);

        return $this->json($prediction);
    }
}