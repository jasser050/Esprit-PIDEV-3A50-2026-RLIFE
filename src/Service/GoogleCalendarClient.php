<?php

namespace App\Service;

use App\Entity\GoogleToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GoogleCalendarClient
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    private function buildClientForUser(User $user): GoogleClient
    {
        $tokenEntity = $this->em->getRepository(GoogleToken::class)->findOneBy(['user' => $user]);
        if (!$tokenEntity) {
            throw new AccessDeniedHttpException('Google Calendar non connecté pour cet utilisateur.');
        }

        $client = new GoogleClient();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
        $client->setAccessType('offline');

        // Charge le token actuel depuis la DB
        $client->setAccessToken([
            'access_token'  => $tokenEntity->getAccessToken(),
            'refresh_token' => $tokenEntity->getRefreshToken(),
            // expires_in = secondes restantes
            'expires_in'    => max(0, $tokenEntity->getExpiresAt()->getTimestamp() - time()),
            'created'       => time(),
        ]);

        // Rafraîchir si expiré
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $tokenEntity->getRefreshToken();
            if (!$refreshToken) {
                throw new AccessDeniedHttpException('Refresh token manquant. Reconnecte Google.');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (!empty($newToken['access_token'])) {
                $tokenEntity->setAccessToken($newToken['access_token']);
            }
            if (!empty($newToken['expires_in'])) {
                $tokenEntity->setExpiresAt(new \DateTimeImmutable('+' . (int) $newToken['expires_in'] . ' seconds'));
            }
            if (!empty($newToken['refresh_token'])) {
                $tokenEntity->setRefreshToken($newToken['refresh_token']);
            }

            $this->em->flush();
        }

        return $client;
    }

    public function listUpcomingEvents(User $user, int $maxResults = 10): array
    {
        $client = $this->buildClientForUser($user);
        $service = new GoogleCalendar($client);

        $events = $service->events->listEvents('primary', [
            'maxResults'   => $maxResults,
            'singleEvents' => true,
            'orderBy'      => 'startTime',
            'timeMin'      => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $out = [];
        foreach ($events->getItems() as $event) {
            $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
            $end   = $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate();

            $out[] = [
                'id'      => $event->getId(),
                'summary' => $event->getSummary(),
                'start'   => $start,
                'end'     => $end,
            ];
        }

        return $out;
    }
    public function createEvent(User $user, string $summary, \DateTimeInterface $start, \DateTimeInterface $end): array
{
    $client = $this->buildClientForUser($user);
    $service = new \Google\Service\Calendar($client);

    $event = new \Google\Service\Calendar\Event([
        'summary' => $summary,
        'start' => ['dateTime' => $start->format(\DateTimeInterface::RFC3339)],
        'end' => ['dateTime' => $end->format(\DateTimeInterface::RFC3339)],
    ]);

    $created = $service->events->insert('primary', $event);

    return [
        'id' => $created->getId(),
        'htmlLink' => $created->getHtmlLink(),
    ];
}
public function listEventsForMonth(User $user, int $year, int $month, int $maxResults = 250): array
{
    $client = $this->buildClientForUser($user);
    $service = new \Google\Service\Calendar($client);

    $tz = new \DateTimeZone('UTC');

    $start = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), $tz);
    $end = $start->modify('first day of next month');

    $events = $service->events->listEvents('primary', [
        'maxResults'   => $maxResults,
        'singleEvents' => true,
        'orderBy'      => 'startTime',
        'timeMin'      => $start->format(\DateTimeInterface::RFC3339),
        'timeMax'      => $end->format(\DateTimeInterface::RFC3339),
    ]);

    $result = [];
    foreach ($events->getItems() as $event) {
        $startObj = $event->getStart();
        $endObj = $event->getEnd();

        $startValue = $startObj->getDateTime() ?: $startObj->getDate(); // dateTime ou all-day date
        $endValue = $endObj->getDateTime() ?: $endObj->getDate();

        $result[] = [
            'id' => $event->getId(),
            'summary' => $event->getSummary(),
            'start' => $startValue,
            'end' => $endValue,
        ];
    }

    return $result;
}
}