<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\GoogleCalendarClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GoogleCalendarApiController extends AbstractController
{
    #[Route('/api/google-calendar/events', name: 'api_google_events', methods: ['GET'])]
    public function events(GoogleCalendarClient $googleCalendar): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'not_authenticated'], 401);
        }

        return $this->json([
            'events' => $googleCalendar->listUpcomingEvents($user, 10),
        ]);
    }
}