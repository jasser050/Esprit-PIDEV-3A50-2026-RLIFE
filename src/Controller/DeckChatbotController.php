<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Repository\FlashcardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/revisions/deck')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DeckChatbotController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $openrouterApiKey = ''
    ) {}

    #[Route('/{id}/chat', name: 'app_deck_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        Deck $deck,
        FlashcardRepository $flashcardRepository
    ): JsonResponse {
        $data        = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');
        $history     = $data['history'] ?? [];

        if ($userMessage === '') {
            return $this->json(['error' => 'Empty message.'], 400);
        }

        $flashcards    = $flashcardRepository->findBy(['deck' => $deck]);
        $knowledgeBase = $this->buildKnowledgeBase($deck, $flashcards);

        // System message + history + user message
        $messages = [['role' => 'system', 'content' => $knowledgeBase]];
        $history  = array_slice($history, -20);
        foreach ($history as $turn) {
            if (in_array($turn['role'] ?? '', ['user', 'assistant'], true)) {
                $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $apiKey = $this->openrouterApiKey ?: ($_ENV['OPENROUTER_API_KEY'] ?? '');

            if (empty($apiKey)) {
                return $this->json(['error' => 'OPENROUTER_API_KEY not configured.'], 500);
            }

            $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => 'http://localhost:8000',
                    'X-Title'       => 'RLIFE Study App',
                ],
                'json' => [
                    'model'      => 'anthropic/claude-3-haiku',
                    'max_tokens' => 1024,
                    'messages'   => $messages,
                ],
                'timeout' => 30,
            ]);

            $body  = $response->toArray();
            $reply = $body['choices'][0]['message']['content'] ?? 'No response received.';

            return $this->json(['reply' => $reply]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'AI service error: ' . $e->getMessage()], 500);
        }
    }

    private function buildKnowledgeBase(Deck $deck, array $flashcards): string
    {
        $cardLines = [];
        foreach ($flashcards as $i => $fc) {
            $cardLines[] = sprintf(
                "Card %d — %s\n  Q: %s\n  A: %s",
                $i + 1,
                $fc->getTitre() ?? 'Untitled',
                $fc->getQuestion(),
                $fc->getReponse()
            );
        }

        $cardsText = empty($cardLines)
            ? '(No flashcards yet.)'
            : implode("\n\n", $cardLines);

        return <<<SYSTEM
You are a smart, friendly AI study assistant specialised in the following deck.

════════════════════════════════════════
DECK INFORMATION
════════════════════════════════════════
Title       : {$deck->getTitre()}
Subject     : {$deck->getMatiere()}
Level       : {$deck->getNiveau()}
Description : {$deck->getDescription()}
Total cards : {$deck->getFlashcards()->count()}

════════════════════════════════════════
FLASHCARD KNOWLEDGE BASE
════════════════════════════════════════
{$cardsText}

════════════════════════════════════════
YOUR ROLE & RULES
════════════════════════════════════════
1. Answer questions ONLY about the content of this deck. If off-topic, politely redirect.
2. You can: explain concepts, quiz the student, give study tips, summarise cards, create mnemonics, suggest a study order.
3. Be encouraging, concise, and pedagogically sound.
4. Reply in the same language the student uses.
5. Use markdown: **bold** for key terms, bullet lists, numbered steps when appropriate.
SYSTEM;
    }
}
