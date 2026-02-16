<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        // Redirect to Google OAuth with account selection prompt
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email',
                'profile'
            ], [
                'prompt' => 'select_account',  // Force account selection screen
                'access_type' => 'offline'
            ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function check(Request $request): Response
    {
        // This route is handled by GoogleAuthenticator
        // It will never actually be executed
        return new Response('This should not be reached');
    }
}
