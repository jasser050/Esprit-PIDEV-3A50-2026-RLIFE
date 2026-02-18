<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai-coach')]
#[IsGranted('ROLE_USER')]
class AICoachController extends AbstractController
{
    #[Route('/', name: 'app_ai_coach')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Obtenir les recommandations IA
        $recommendations = $user->getAIRecommendations();
        
        return $this->render('pages/ai_coach/dashboard.html.twig', [
            'recommendations' => $recommendations,
            'user_stats' => [
                'total_evaluations' => $user->getEvaluations()->count(),
                'overall_average' => $user->calculateOverallAverage(),
                'current_streak' => $user->getHighScoreStreak()['current'],
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
        
        $recommendations = $user->getAIRecommendations();
        
        return $this->json($recommendations);
    }
    
    #[Route('/study-schedule', name: 'app_ai_coach_schedule')]
    public function studySchedule(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $recommendations = $user->getAIRecommendations();
        
        return $this->render('pages/ai_coach/schedule.html.twig', [
            'schedule' => $recommendations['study_schedule'],
            'at_risk' => $recommendations['at_risk_subjects'],
        ]);
    }
}