<?php

namespace App\Controller;

use App\Data\SampleData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/planning')]
class PlanningController extends AbstractController
{
    #[Route('', name: 'app_planning')]
    public function index(Request $request): Response
    {
        $events = SampleData::getEvents();
        $today = new \DateTime();
        
        $month = $request->query->getInt('month', (int)$today->format('n'));
        $year = $request->query->getInt('year', (int)$today->format('Y'));
        
        $currentDate = new \DateTime("$year-$month-01");
        $daysInMonth = (int)$currentDate->format('t');
        $firstDayOfWeek = (int)$currentDate->format('N');
        
        // Filter events for current month
        $monthEvents = array_filter($events, function($e) use ($year, $month) {
            $eventDate = new \DateTime($e['date']);
            return (int)$eventDate->format('n') === $month && (int)$eventDate->format('Y') === $year;
        });

        // Group events by date
        $eventsByDate = [];
        foreach ($monthEvents as $event) {
            $date = $event['date'];
            if (!isset($eventsByDate[$date])) {
                $eventsByDate[$date] = [];
            }
            $eventsByDate[$date][] = $event;
        }

        // Find conflicts
        $conflicts = array_filter($events, fn($e) => isset($e['conflict']) && $e['conflict']);

        return $this->render('pages/planning/index.html.twig', [
            'current_month' => $month,
            'current_year' => $year,
            'days_in_month' => $daysInMonth,
            'first_day_of_week' => $firstDayOfWeek,
            'events_by_date' => $eventsByDate,
            'conflicts' => array_values($conflicts),
            'today' => $today->format('Y-m-d'),
        ]);
    }

    #[Route('/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // In a real app, we would save the event here
            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('app_planning');
        }

        $courses = SampleData::getCourses();
        $preselectedDate = $request->query->get('date');

        return $this->render('pages/planning/new.html.twig', [
            'courses' => $courses,
            'preselected_date' => $preselectedDate,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_planning_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $events = SampleData::getEvents();
        $event = null;

        foreach ($events as $e) {
            if ($e['id'] === $id) {
                $event = $e;
                break;
            }
        }

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        if ($request->isMethod('POST')) {
            // In a real app, we would update the event here
            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('app_planning');
        }

        $courses = SampleData::getCourses();

        return $this->render('pages/planning/edit.html.twig', [
            'event' => $event,
            'courses' => $courses,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_planning_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        // In a real app, we would delete the event here
        $this->addFlash('success', 'Event deleted successfully!');
        return $this->redirectToRoute('app_planning');
    }
}
