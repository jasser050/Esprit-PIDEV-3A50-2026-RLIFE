<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/stats')]
#[IsGranted('ROLE_USER')]
class StatsController extends AbstractController
{
    #[Route('/', name: 'app_stats_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('pages/stats/dashboard.html.twig', [
            'history' => $user->getStreakHistory(30),
            'monthly_performance' => $user->getPerformanceByPeriod('month'),
            'weekly_performance' => $user->getPerformanceByPeriod('week'),
            'prediction' => $user->predictNextScore(),
            'complete_stats' => $user->getCompleteStatistics(),
        ]);
    }
    
    #[Route('/chart-data', name: 'app_stats_chart_data')]
    public function chartData(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }
        
        $history = $user->getStreakHistory(30);
        
        return $this->json([
            'labels' => array_column($history, 'date'),
            'streaks' => array_column($history, 'streak'),
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
        
        $performance = $user->getPerformanceByPeriod($period);
        
        return $this->json($performance);
    }
    
    #[Route('/prediction', name: 'app_stats_prediction')]
    public function prediction(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }
        
        $prediction = $user->predictNextScore();
        
        return $this->json($prediction);
    }
}