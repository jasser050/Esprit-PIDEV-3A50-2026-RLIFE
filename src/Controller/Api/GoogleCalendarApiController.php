<?php

namespace App\Controller\Api;

use App\Service\GoogleCalendarClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/google-calendar')]
class GoogleCalendarApiController extends AbstractController
{
    #[Route('/events', name: 'api_google_events', methods: ['GET'])]
    public function events(Request $request, GoogleCalendarClient $client): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $start = (string) $request->query->get('start', '');
        $end = (string) $request->query->get('end', '');

        if ($start === '' || $end === '') {
            return $this->json(['error' => 'start and end required'], 400);
        }

        $data = $client->listEvents(
            $user,
            new \DateTimeImmutable($start.' 00:00:00'),
            new \DateTimeImmutable($end.' 23:59:59')
        );

        return $this->json($data);
    }
}