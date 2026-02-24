<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PusherTestController extends AbstractController
{
    #[Route('/test-pusher', name: 'test_pusher')]
    public function testPusher(): Response
    {
        // DÉSACTIVER TEMPORAIREMENT LA VÉRIFICATION SSL GLOBALEMENT
        $originalVerifyPeer = ini_get('curl.cainfo');
        $originalVerifyHost = ini_get('openssl.cafile');
        
        // Désactiver temporairement
        ini_set('curl.cainfo', '');
        ini_set('openssl.cafile', '');

        try {
            $pusher = new \Pusher\Pusher(
                $_ENV['PUSHER_KEY'],
                $_ENV['PUSHER_SECRET'],
                $_ENV['PUSHER_APP_ID'],
                [
                    'cluster' => $_ENV['PUSHER_CLUSTER'],
                    'useTLS' => false,  // ← CHANGÉ À false
                    'curl_options' => [
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_TIMEOUT => 10
                    ]
                ]
            );

            $result = $pusher->trigger('test-channel', 'test-event', [
                'message' => 'Hello from Pusher!',
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // Restaurer les paramètres
            if ($originalVerifyPeer) ini_set('curl.cainfo', $originalVerifyPeer);
            if ($originalVerifyHost) ini_set('openssl.cafile', $originalVerifyHost);

            return $this->render('test_pusher.html.twig');

        } catch (\Exception $e) {
            // Restaurer les paramètres même en cas d'erreur
            if ($originalVerifyPeer) ini_set('curl.cainfo', $originalVerifyPeer);
            if ($originalVerifyHost) ini_set('openssl.cafile', $originalVerifyHost);

            return new Response('
                <h1 style="color: red;">❌ Erreur Pusher</h1>
                <p><strong>Message :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                <hr>
                <h3>Essayez cette solution :</h3>
                <pre style="background: #f5f5f5; padding: 15px;">
# Créer le dossier
New-Item -Path "C:\php\extras\ssl" -ItemType Directory -Force

# Télécharger le certificat
Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "C:\php\extras\ssl\cacert.pem"

# Redémarrer Symfony
symfony server:stop
symfony server:start
                </pre>
                <p><a href="/">← Retour</a></p>
            ', 500);
        }
    }
}