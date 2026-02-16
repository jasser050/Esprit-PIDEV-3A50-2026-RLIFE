<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/voice-assistant')]
#[IsGranted('ROLE_USER')]
class VoiceAssistantController extends AbstractController
{
    #[Route('/', name: 'app_voice_assistant')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        return $this->render('pages/voice_assistant/index.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/save-note', name: 'app_voice_save_note', methods: ['POST'])]
    public function saveNote(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $transcript = $data['transcript'] ?? '';
        $subject = $data['subject'] ?? 'General';
        
        if (empty($transcript)) {
            return $this->json([
                'success' => false,
                'message' => 'Transcript is empty'
            ], 400);
        }
        
        // Ici tu peux sauvegarder dans la base de données
        // Pour l'instant, on retourne juste une confirmation
        
        return $this->json([
            'success' => true,
            'message' => 'Note saved successfully',
            'data' => [
                'transcript' => $transcript,
                'subject' => $subject,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}