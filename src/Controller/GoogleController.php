<?php

namespace App\Controller;

use App\Entity\GoogleToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/oauth/google/connect', name: 'app_google_connect')]
    public function connectGoogle(): Response
    {
        $client = new \Google\Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
        $client->addScope(\Google\Service\Calendar::CALENDAR);

        $authUrl = $client->createAuthUrl();

        return $this->redirect($authUrl);
    }

    #[Route('/oauth/google/callback', name: 'app_google_callback')]
    public function connectGoogleCheck(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'No authorization code returned from Google.');
            return $this->redirectToRoute('app_planning');
        }

        $client = new \Google\Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                $this->addFlash('error', 'Google OAuth error: ' . $token['error_description']);
                return $this->redirectToRoute('app_planning');
            }

            // Supprimer ancien token si existant
            $oldToken = $em->getRepository(GoogleToken::class)->findOneBy(['user' => $user]);
            if ($oldToken) {
                $em->remove($oldToken);
                $em->flush();
            }

            // Créer nouveau token
            $googleToken = new GoogleToken();
            $googleToken->setUser($user);
            $googleToken->setAccessToken($token['access_token']);
            $googleToken->setRefreshToken($token['refresh_token'] ?? null);
            $googleToken->setExpiresAt(
                new \DateTimeImmutable('+' . ($token['expires_in'] ?? 3600) . ' seconds')
            );

            $em->persist($googleToken);
            $em->flush();

            $this->addFlash('success', 'Google Calendar connected successfully!');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Google OAuth exception: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_planning');
    }
}
