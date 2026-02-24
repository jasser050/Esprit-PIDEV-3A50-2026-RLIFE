<?php

namespace App\Controller;

use App\Entity\GoogleToken;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Google;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleOAuthController extends AbstractController
{
    private const SESSION_STATE_KEY = 'google_oauth2_state';

    private function provider(): Google
    {
        return new Google([
            'clientId'     => $_ENV['GOOGLE_CLIENT_ID'],
            'clientSecret' => $_ENV['GOOGLE_CLIENT_SECRET'],
            'redirectUri'  => $_ENV['GOOGLE_REDIRECT_URI'],
        ]);
    }

    #[Route('/oauth/google/connect', name: 'app_google_connect', methods: ['GET'])]
    public function connect(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $session = $request->getSession();
        if (!$session) {
            throw $this->createAccessDeniedException('Session non disponible.');
        }

        $provider = $this->provider();

        $authUrl = $provider->getAuthorizationUrl([
            'scope' => [
                'openid',
                'email',
                'profile',
                'https://www.googleapis.com/auth/calendar',
            ],
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        $session->set(self::SESSION_STATE_KEY, $provider->getState());
        $session->save();

        return $this->redirect($authUrl);
    }

    #[Route('/oauth/google/callback', name: 'app_google_callback', methods: ['GET'])]
    public function callback(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $session = $request->getSession();
        if (!$session) {
            throw $this->createAccessDeniedException('Session non disponible.');
        }

        $expectedState = (string) $session->get(self::SESSION_STATE_KEY, '');
        $receivedState = (string) $request->query->get('state', '');

        if ($expectedState === '' || $receivedState === '' || !hash_equals($expectedState, $receivedState)) {
            return new Response(sprintf(
                "Invalid OAuth state\nExpected: %s\nReceived: %s\nSessionId: %s\n",
                $expectedState ?: '(empty)',
                $receivedState ?: '(empty)',
                $session->getId()
            ), 403);
        }

        $code = (string) $request->query->get('code', '');
        if ($code === '') {
            $this->addFlash('error', 'Google OAuth code manquant.');
            return $this->redirectToRoute('app_planning');
        }

        $provider = $this->provider();
        $token = $provider->getAccessToken('authorization_code', ['code' => $code]);

        $repo = $em->getRepository(GoogleToken::class);
        $gt = $repo->findOneBy(['user' => $user]) ?? (new GoogleToken())->setUser($user);

        $gt->setAccessToken($token->getToken());
        $gt->setRefreshToken($token->getRefreshToken());
        $gt->setExpiresAt((new \DateTimeImmutable())->setTimestamp($token->getExpires()));

        $em->persist($gt);
        $em->flush();

        $session->remove(self::SESSION_STATE_KEY);

        $this->addFlash('success', 'Google Calendar connecté.');
        return $this->redirectToRoute('app_planning');
    }
}
