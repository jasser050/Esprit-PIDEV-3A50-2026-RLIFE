<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test-email', name: 'test_email', methods: ['GET'])]
    public function testEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('samar.masmoudi2@gmail.com')
            ->to('samar.masmoudi2@gmail.com')
            ->subject('Test RLIFE - Notification Gmail SMTP')
            ->text('Ceci est un test d\'envoi depuis Gmail SMTP pour RLIFE.')
            ->html('
                <h1>Test Notification RLIFE</h1>
                <p>Si tu vois ce message dans ta boîte Gmail, l\'intégration fonctionne parfaitement !</p>
                <p>Tu peux maintenant recevoir les notifications d\'échéance des tâches et projets.</p>
                <p>Cordialement,<br>Samar - RLIFE</p>
            ');

        try {
            $mailer->send($email);
            return new Response('
                <div style="text-align: center; padding: 40px; font-family: Arial;">
                    <h1 style="color: green;">SUCCÈS !</h1>
                    <p>Email envoyé avec succès.</p>
                    <p>Vérifie ta boîte Gmail (Inbox, Spam, Promotions).</p>
                    <p><a href="/" style="color: #3b82f6; text-decoration: none;">Retour à l\'accueil</a></p>
                </div>
            ');
        } catch (\Exception $e) {
            return new Response('
                <div style="text-align: center; padding: 40px; font-family: Arial;">
                    <h1 style="color: red;">Erreur</h1>
                    <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    <p><a href="/" style="color: #3b82f6; text-decoration: none;">Retour</a></p>
                </div>
            ', 500);
        }
    }
}
