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
    // ── Page principale ───────────────────────────────────────────────
    #[Route('/', name: 'app_voice_assistant')]
    public function index(): Response
    {
        return $this->render('pages/voice_assistant/index.html.twig', [
            'user'       => $this->getUser(),
        'gemini_key' => $this->getParameter('gemini_api_key'),
        ]);
    }

    // ── Save note in session ──────────────────────────────────────────
    #[Route('/save-note', name: 'app_voice_save_note', methods: ['POST'])]
    public function saveNote(Request $request): JsonResponse
    {
        $data       = json_decode($request->getContent(), true);
        $transcript = trim($data['transcript'] ?? $data['text'] ?? '');
        $subject    = $data['subject'] ?? 'General';
        $words      = (int) ($data['words'] ?? str_word_count($transcript));

        if (empty($transcript)) {
            return $this->json([
                'success' => false,
                'message' => 'Transcript is empty',
            ], 400);
        }

        $session = $request->getSession();
        $notes   = $session->get('voice_notes', []);

        $note = [
            'id'        => uniqid('note_', true),
            'text'      => $transcript,
            'subject'   => $subject,
            'words'     => $words,
            'date'      => (new \DateTime())->format('M d, H:i'),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        array_unshift($notes, $note);

        if (count($notes) > 50) {
            $notes = array_slice($notes, 0, 50);
        }

        $session->set('voice_notes', $notes);

        return $this->json([
            'success' => true,
            'message' => 'Note saved successfully',
            'note'    => $note,
        ]);
    }

    // ── Get all notes ─────────────────────────────────────────────────
    #[Route('/notes', name: 'app_voice_assistant_notes', methods: ['GET'])]
    public function getNotes(Request $request): JsonResponse
    {
        $notes = $request->getSession()->get('voice_notes', []);
        return $this->json(['notes' => $notes]);
    }

    // ── Delete one note ───────────────────────────────────────────────
    #[Route('/notes/{id}', name: 'app_voice_assistant_delete_note', methods: ['DELETE'])]
    public function deleteNote(string $id, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $notes   = $session->get('voice_notes', []);
        $notes   = array_values(array_filter($notes, fn($n) => $n['id'] !== $id));
        $session->set('voice_notes', $notes);

        return $this->json(['success' => true]);
    }

    // ── Clear all notes ───────────────────────────────────────────────
    #[Route('/notes', name: 'app_voice_assistant_clear_notes', methods: ['DELETE'])]
    public function clearNotes(Request $request): JsonResponse
    {
        $request->getSession()->remove('voice_notes');
        return $this->json(['success' => true]);
    }
}