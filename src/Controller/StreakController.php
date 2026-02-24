<?php

namespace App\Controller;

use App\Service\StreakService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/streak')]
#[IsGranted('ROLE_USER')]
class StreakController extends AbstractController
{
    public function __construct(
        private StreakService $streakService,
    ) {}

    #[Route('/', name: 'app_streak_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/streak/dashboard.html.twig', [
            // ✅ Avant : $user->getAllStreakStats()
            'globalStats'      => $this->streakService->getAllStreakStats($user),

            // ✅ Avant : $user->getStreaksByMatiere()
            'streaksByMatiere' => $this->streakService->getStreaksByMatiere($user),

            // ✅ Avant : $user->getAllBadges()
            'allBadges'        => $this->streakService->getAllBadges($user),
        ]);
    }
}