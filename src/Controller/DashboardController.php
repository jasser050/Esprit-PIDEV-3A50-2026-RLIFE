<?php

namespace App\Controller;

use App\Data\SampleData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(Request $request): Response
    {
        // Redirect admins to admin dashboard
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        
        $today = new \DateTime();
        $assignments = SampleData::getAssignments();
        $events = SampleData::getEvents();
        $stats = SampleData::getStats();
        $activityFeed = SampleData::getActivityFeed();
        $courses = SampleData::getCourses();

        // Filter for today's events
        $todayEvents = array_filter($events, fn($e) => $e['date'] === $today->format('Y-m-d'));
        
        // Filter for upcoming assignments (next 7 days, not completed)
        $upcomingAssignments = array_filter($assignments, function($a) use ($today) {
            $dueDate = new \DateTime($a['due_date']);
            $diff = $today->diff($dueDate)->days;
            return $a['status'] !== 'completed' && $diff >= 0 && $diff <= 7;
        });
        
        usort($upcomingAssignments, fn($a, $b) => strcmp($a['due_date'], $b['due_date']));

        $aiPlan = $request->getSession()->get('ai_productivity_plan', []);
        $aiChallenges = $request->getSession()->get('ai_productivity_challenges', []);
        if (!is_array($aiChallenges)) {
            $aiChallenges = [];
        }

        return $this->render('pages/dashboard.html.twig', [
            'stats' => $stats,
            'today_events' => array_values($todayEvents),
            'upcoming_assignments' => array_slice($upcomingAssignments, 0, 5),
            'activity_feed' => $activityFeed,
            'courses' => $courses,
            'ai_plan' => is_array($aiPlan) ? $aiPlan : [],
            'ai_challenges' => $aiChallenges,
        ]);
    }
}
