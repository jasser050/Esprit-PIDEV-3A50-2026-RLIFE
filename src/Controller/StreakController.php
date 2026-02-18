<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/streak')]
#[IsGranted('ROLE_USER')] // ← AJOUTE CETTE LIGNE
class StreakController extends AbstractController
{
    #[Route('/', name: 'app_streak_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        
        // Vérification supplémentaire (au cas où)
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Statistiques globales
        $globalStats = $user->getAllStreakStats();
        
        // Streaks par matière
        $streaksByMatiere = $user->getStreaksByMatiere();
        
        // Tous les badges
        $allBadges = $user->getAllBadges();
        
        return $this->render('pages/streak/dashboard.html.twig', [
            'globalStats' => $globalStats,
            'streaksByMatiere' => $streaksByMatiere,
            'allBadges' => $allBadges,
        ]);
    }
}