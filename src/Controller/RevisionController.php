<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Entity\StudentGamification;
use App\Form\DeckType;
use App\Form\FlashcardType;
use App\Repository\DeckRepository;
use App\Repository\FlashcardRepository;
use App\Repository\StudentGamificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// Imports Endroid QR Code
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Label\LabelAlignment;

#[Route('/revisions')]
class RevisionController extends AbstractController
{
    // ─────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────

    #[Route('', name: 'app_revisions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        DeckRepository $deckRepository,
        StudentGamificationRepository $gamificationRepository,
        EntityManagerInterface $em
    ): Response {
        $user  = $this->getUser();
        $decks = $deckRepository->findAll();

        $gamification = $gamificationRepository->findOneBy(['user' => $user]);

        if (!$gamification) {
            $gamification = new StudentGamification();
            $gamification->setUser($user);
            $em->persist($gamification);
            $em->flush();
        }

        return $this->render('pages/revisions/index.html.twig', [
            'decks'        => $decks,
            'gamification' => $gamification,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // NEW DECK
    // ─────────────────────────────────────────────────────────────

    #[Route('/new', name: 'app_revisions_deck_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newDeck(Request $request, EntityManagerInterface $em): Response
    {
        $deck = new Deck();
        $deck->setUser($this->getUser());

        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $deck->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload image');
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $deck->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload PDF');
                }
            }

            $deck->setDateCreation(new \DateTime());
            $em->persist($deck);
            $em->flush();

            $this->addFlash('success', 'Deck créé avec succès !');
            return $this->redirectToRoute('app_revisions');
        }

        return $this->render('pages/revisions/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // STUDY
    // ─────────────────────────────────────────────────────────────

    #[Route('/deck/{id}', name: 'app_revisions_deck_study', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function study(Deck $deck): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_revisions');
        }

        return $this->render('pages/revisions/study.html.twig', [
            'deck'       => $deck,
            'flashcards' => $deck->getFlashcards(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // NEW FLASHCARD
    // ─────────────────────────────────────────────────────────────

    #[Route('/flashcard/new/{deck_id}', name: 'app_flashcard_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newFlashcard(
        int $deck_id,
        DeckRepository $deckRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $deck = $deckRepository->find($deck_id);

        if (!$deck) {
            throw $this->createNotFoundException('Deck introuvable');
        }

        $flashcard = new Flashcard();
        $flashcard->setDeck($deck);

        $form = $this->createForm(FlashcardType::class, $flashcard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/flashcards/';

            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $flashcard->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload image');
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $flashcard->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload PDF');
                }
            }

            $em->persist($flashcard);
            $em->flush();

            $this->addFlash('success', 'Flashcard ajoutée avec succès !');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
        }

        return $this->render('pages/flashcard/new.html.twig', [
            'form' => $form->createView(),
            'deck' => $deck,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // ✨ AI GUIDE — GUIDE PÉDAGOGIQUE IA
    // ─────────────────────────────────────────────────────────────

    #[Route('/flashcard/ai-guide/{deck_id}', name: 'app_flashcard_ai_guide', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function aiGuide(
        int $deck_id,
        DeckRepository $deckRepository
    ): Response {
        $deck = $deckRepository->find($deck_id);

        if (!$deck) {
            throw $this->createNotFoundException('Deck introuvable');
        }

        return $this->render('pages/flashcard/ai_guide.html.twig', [
            'deck'   => $deck,
            'deckId' => $deck_id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // EDIT FLASHCARD
    // ─────────────────────────────────────────────────────────────

    #[Route('/flashcard/{id}/edit', name: 'app_flashcard_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editFlashcard(
        Flashcard $flashcard,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $deck = $flashcard->getDeck();

        $form = $this->createForm(FlashcardType::class, $flashcard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/flashcards/';

            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $flashcard->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload image');
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $flashcard->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload PDF');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Flashcard modifiée avec succès !');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
        }

        return $this->render('pages/flashcard/edit.html.twig', [
            'form'      => $form->createView(),
            'flashcard' => $flashcard,
            'deck'      => $deck,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // DELETE FLASHCARD
    // ─────────────────────────────────────────────────────────────

    #[Route('/flashcard/{id}', name: 'app_flashcard_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteFlashcard(
        Flashcard $flashcard,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $deck = $flashcard->getDeck();

        if ($this->isCsrfTokenValid('delete' . $flashcard->getId(), $request->request->get('_token'))) {
            $em->remove($flashcard);
            $em->flush();
            $this->addFlash('success', 'Flashcard supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
    }

    // ─────────────────────────────────────────────────────────────
    // AI FLASHCARD GENERATOR
    // ─────────────────────────────────────────────────────────────

    #[Route('/deck/{id}/generate-flashcards', name: 'app_deck_generate_flashcards', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateFlashcards(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        DeckRepository $deckRepository,
        HttpClientInterface $httpClient
    ): Response {
        $deck = $deckRepository->find($id);
        if (!$deck) {
            throw $this->createNotFoundException('Deck introuvable');
        }

        $theme = trim($request->request->get('theme', ''));
        $count = max(1, min(10, (int)($request->request->get('count', 5))));

        if (empty($theme)) {
            $this->addFlash('error', '❌ Veuillez décrire un thème à générer.');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
        }

        $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? null;

        try {
            if ($apiKey) {
                $prompt = <<<PROMPT
Tu es un professeur expert en création de flashcards pédagogiques.
Génère exactement {$count} flashcard(s) différentes sur le thème : "{$theme}"
Niveau du deck : {$deck->getNiveau()}
Matière : {$deck->getMatiere()}
Chaque flashcard doit couvrir un aspect différent du thème.
Réponds UNIQUEMENT avec un tableau JSON valide (sans markdown, sans texte avant ou après) :
[
  {
    "titre": "Titre court et accrocheur (max 60 chars)",
    "question": "Question précise et claire pour un étudiant",
    "reponse": "Réponse complète, pédagogique (2-4 phrases)",
    "description": "Conseil mémo ou contexte (1 phrase)",
    "difficulte": 3
  }
]
IMPORTANT : Retourne exactement {$count} objet(s) dans le tableau. Aucun texte hors du JSON.
PROMPT;

                $response = $httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => 'https://rlife.app',
                        'X-Title' => 'RLIFE Flashcards Generator',
                    ],
                    'json' => [
                        'model' => 'mistralai/mistral-7b-instruct',
                        'max_tokens' => 300 + ($count * 250),
                        'temperature' => 0.75,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Tu génères des flashcards pédagogiques. Réponds UNIQUEMENT avec un tableau JSON valide, sans texte avant ou après, sans markdown.'
                            ],
                            ['role' => 'user', 'content' => $prompt]
                        ],
                    ],
                    'timeout' => 30,
                ]);

                $aiData = $response->toArray();
                $content = $aiData['choices'][0]['message']['content'] ?? '[]';

                $content = preg_replace('/```json\s*/i', '', $content);
                $content = preg_replace('/```/', '', $content);
                $content = trim($content);

                if (preg_match('/\[.*\]/s', $content, $matches)) {
                    $content = $matches[0];
                }

                $parsed = json_decode($content, true);

                if (is_array($parsed) && isset($parsed['question'])) {
                    $parsed = [$parsed];
                }

                if (!is_array($parsed) || empty($parsed)) {
                    throw new \RuntimeException('Réponse IA invalide.');
                }

                $created = 0;
                foreach ($parsed as $item) {
                    if (empty($item['question']) || empty($item['reponse'])) {
                        continue;
                    }

                    $flashcard = new Flashcard();
                    $flashcard->setDeck($deck);
                    $flashcard->setCreatedBy($this->getUser());
                    $flashcard->setTitre($item['titre'] ?? '🤖 IA : ' . substr($theme, 0, 45));
                    $flashcard->setQuestion($item['question']);
                    $flashcard->setReponse($item['reponse']);
                    $flashcard->setDescription($item['description'] ?? '');
                    $flashcard->setNiveauDifficulte((int)($item['difficulte'] ?? 3));
                    $flashcard->setEtat('actif');

                    $em->persist($flashcard);
                    $created++;
                }

                $em->flush();

                if ($created === 0) {
                    throw new \RuntimeException('Aucune flashcard valide générée.');
                }

                $this->addFlash('success', sprintf(
                    '✅ %d flashcard%s générée%s sur "%s" !',
                    $created,
                    $created > 1 ? 's' : '',
                    $created > 1 ? 's' : '',
                    $theme
                ));
            } else {
                for ($i = 0; $i < $count; $i++) {
                    $flashcard = new Flashcard();
                    $flashcard->setDeck($deck);
                    $flashcard->setCreatedBy($this->getUser());
                    $flashcard->setTitre(sprintf('📝 %s (#%d)', substr($theme, 0, 45), $i + 1));
                    $flashcard->setQuestion(sprintf('Question %d sur : %s', $i + 1, $theme));
                    $flashcard->setReponse(sprintf('Réponse à compléter pour la flashcard %d sur "%s". ', $i + 1, $theme) .
                        'Ajoutez OPENROUTER_API_KEY dans .env pour des réponses IA réelles.');
                    $flashcard->setDescription('Généré le ' . date('d/m/Y à H:i'));
                    $flashcard->setNiveauDifficulte(2);
                    $flashcard->setEtat('actif');

                    $em->persist($flashcard);
                }
                $em->flush();

                $this->addFlash('success', sprintf(
                    '✅ %d flashcard%s créée%s (mode sans IA).',
                    $count,
                    $count > 1 ? 's' : '',
                    $count > 1 ? 's' : ''
                ));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur génération IA : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
    }

    // ─────────────────────────────────────────────────────────────
    // VOCAL AI ANALYSIS
    // ─────────────────────────────────────────────────────────────

    #[Route('/api/analyse-vocal', name: 'app_api_analyse_vocal', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function analyseVocal(
        Request $request,
        FlashcardRepository $flashcardRepository,
        HttpClientInterface $httpClient
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Corps de requête invalide'], 400);
        }

        $token = $data['_token'] ?? '';
        if (!$this->isCsrfTokenValid('analyse_vocal', $token)) {
            return $this->json(['error' => 'Token CSRF invalide'], 403);
        }

        $flashcardId = (int)($data['flashcardId'] ?? 0);
        $reponseEtudiant = trim($data['reponseEtudiant'] ?? '');

        if (!$flashcardId || empty($reponseEtudiant)) {
            return $this->json(['error' => 'flashcardId et reponseEtudiant sont requis'], 400);
        }

        $flashcard = $flashcardRepository->find($flashcardId);
        if (!$flashcard) {
            return $this->json(['error' => 'Flashcard introuvable'], 404);
        }

        $bonneReponse = $flashcard->getReponse();
        $question = $flashcard->getQuestion();

        $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? null;

        if (!$apiKey) {
            $score = $this->simulateScore($reponseEtudiant, $bonneReponse);
            return $this->json([
                'score' => $score,
                'feedback' => $this->simulateFeedback($score),
                'simulated' => true,
            ]);
        }

        $prompt = <<<PROMPT
Tu es un professeur bienveillant qui évalue la réponse d'un étudiant.
QUESTION : {$question}
BONNE RÉPONSE : {$bonneReponse}
RÉPONSE ÉTUDIANT : {$reponseEtudiant}
Évalue de 0 à 10. Sois tolérant aux synonymes et reformulations correctes.
Réponds UNIQUEMENT avec ce JSON (sans markdown) :
{"score": 7, "feedback": "Feedback clair et encourageant de 1-2 phrases."}
PROMPT;

        try {
            $response = $httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'https://rlife.app',
                    'X-Title' => 'RLIFE Vocal Analysis',
                ],
                'json' => [
                    'model' => 'mistralai/mistral-7b-instruct',
                    'max_tokens' => 150,
                    'temperature' => 0.2,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un évaluateur pédagogique. Réponds uniquement en JSON valide.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ],
                'timeout' => 15,
            ]);

            $aiData = $response->toArray();
            $content = $aiData['choices'][0]['message']['content'] ?? '{}';

            $content = preg_replace('/```json\s*/i', '', $content);
            $content = preg_replace('/```/', '', $content);
            $content = trim($content);

            if (preg_match('/\{.*\}/s', $content, $m)) {
                $content = $m[0];
            }

            $parsed = json_decode($content, true);

            if (!$parsed || !isset($parsed['score'])) {
                throw new \RuntimeException('Réponse IA mal formée');
            }

            $score = max(0, min(10, (int)$parsed['score']));
            $this->updateSpacedRepetition($flashcard, $score);

            return $this->json([
                'score' => $score,
                'feedback' => $parsed['feedback'] ?? 'Analyse effectuée.',
                'simulated' => false,
            ]);
        } catch (\Exception $e) {
            $score = $this->simulateScore($reponseEtudiant, $bonneReponse);
            return $this->json([
                'score' => $score,
                'feedback' => $this->simulateFeedback($score) . ' (mode local)',
                'simulated' => true,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 🤖 DÉTECTION AUTOMATIQUE DES CARTES MAL FORMULÉES
    // ─────────────────────────────────────────────────────────────

    #[Route('/deck/{id}/analyze', name: 'app_deck_analyze', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function analyzeCards(
        Deck $deck,
        FlashcardRepository $flashcardRepository,
        HttpClientInterface $httpClient
    ): JsonResponse {
        $flashcards = $flashcardRepository->findBy(['deck' => $deck]);

        if (empty($flashcards)) {
            return $this->json(['error' => 'Aucune carte à analyser.'], 400);
        }

        $cardsText = '';
        foreach ($flashcards as $i => $fc) {
            $cardsText .= sprintf(
                "Carte %d (ID:%d)\nTitre: %s\nQuestion: %s\nRéponse: %s\n\n",
                $i + 1,
                $fc->getId(),
                $fc->getTitre() ?? 'Sans titre',
                $fc->getQuestion(),
                $fc->getReponse()
            );
        }

        $prompt = <<<PROMPT
Tu es un expert pédagogique. Analyse ces flashcards et détecte les problèmes de formulation.

Retourne UNIQUEMENT un objet JSON avec ce format EXACT (rien d'autre, pas de markdown) :
{
  "score_global": <note de qualité globale du deck de 0 à 10>,
  "cartes": [
    {
      "id": <ID de la carte>,
      "titre": "<titre de la carte>",
      "problemes": ["<problème 1>", "<problème 2>"],
      "suggestion": "<suggestion d'amélioration concrète>",
      "severite": "faible|moyen|grave",
      "question_corrigee": "<nouvelle version améliorée de la question>",
      "reponse_corrigee": "<nouvelle version améliorée de la réponse>"
    }
  ]
}

Types de problèmes à détecter :
- "Question trop vague" : question générale sans précision
- "Réponse trop longue" : réponse de plus de 3 phrases
- "Carte ambiguë" : question ou réponse peut être interprétée de plusieurs façons
- "Trop complexe" : contient plusieurs questions/concepts dans une seule carte
- "Question fermée" : question à laquelle on répond juste par oui/non
- "Réponse incomplète" : réponse trop courte ou vague

Règles :
- Si une carte est bien formulée, ne l'inclus PAS dans "cartes"
- Si toutes les cartes sont bonnes, "cartes" = []
- "score_global" = 10 si tout est parfait, baisse selon le nombre et la gravité des problèmes
- "question_corrigee" et "reponse_corrigee" sont TOUJOURS présents pour les cartes problématiques

Voici les cartes à analyser :

{$cardsText}
PROMPT;

        try {
            $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';

            if (empty($apiKey)) {
                return $this->json(['error' => 'Clé API non configurée.'], 500);
            }

            $response = $httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => 'http://localhost:8000',
                    'X-Title'       => 'RLIFE Study App',
                ],
                'json' => [
                    'model'      => 'anthropic/claude-3-haiku',
                    'max_tokens' => 2048,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ],
                'timeout' => 45,
            ]);

            $body    = $response->toArray();
            $content = $body['choices'][0]['message']['content'] ?? '{}';

            $content = preg_replace('/^```json\s*/i', '', trim($content));
            $content = preg_replace('/\s*```$/', '', $content);

            $parsed = json_decode($content, true);

            $scoreGlobal = $parsed['score_global'] ?? 10;
            $cartes      = $parsed['cartes'] ?? [];

            if (!is_array($cartes)) {
                $cartes = [];
            }

            foreach ($cartes as &$carte) {
                $fcId = $carte['id'] ?? null;
                if ($fcId) {
                    foreach ($flashcards as $fc) {
                        if ($fc->getId() === $fcId) {
                            $carte['question_originale'] = $fc->getQuestion();
                            $carte['reponse_originale']  = $fc->getReponse();
                            break;
                        }
                    }
                }
            }

            return $this->json([
                'total'        => count($flashcards),
                'problemes'    => count($cartes),
                'score_global' => $scoreGlobal,
                'cartes'       => $cartes,
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur IA : ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // QR CODE GENERATION
    // ─────────────────────────────────────────────────────────────

    #[Route('/deck/{id}/qrcode', name: 'app_deck_qrcode', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function generateQrCode(
        Deck $deck,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $shareToken = $this->generateShareToken($deck);
        $shareUrl = $urlGenerator->generate(
            'app_qrcode_import',
            ['token' => $shareToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $builder = Builder::create()
                ->writer(new PngWriter())
                ->data($shareUrl)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(420)
                ->margin(20)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin);

            $titre = $deck->getTitre() ?? 'Deck partagé';
            $titreCourt = mb_substr($titre, 0, 38, 'UTF-8');

            $builder
                ->labelText($titreCourt)
                ->labelTextColor(new Color(0, 0, 0))
                ->labelAlignment(LabelAlignment::Center)
                ->labelFontSize(15);

            $result = $builder->build();

            return new Response(
                $result->getString(),
                Response::HTTP_OK,
                [
                    'Content-Type'      => $result->getMimeType(),
                    'Content-Length'    => $result->getSize(),
                    'Cache-Control'     => 'no-cache, private',
                ]
            );
        } catch (\Throwable $e) {
            $msg = "Erreur QR Code\n\nMessage: " . $e->getMessage() . "\nDeck ID: " . $deck->getId() . "\nURL: " . $shareUrl . "\n\n" . $e->getTraceAsString();

            return new Response(
                '<pre style="background:#fee; color:#c00; padding:20px; font-family:monospace; white-space:pre-wrap;">' .
                htmlspecialchars($msg) .
                '</pre>',
                500,
                ['Content-Type' => 'text/html; charset=utf-8']
            );
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SHARE & IMPORT
    // ─────────────────────────────────────────────────────────────

    #[Route('/deck/{id}/share', name: 'app_deck_share', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function shareDeck(Deck $deck): Response
    {
        if ($deck->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce deck.');
        }

        $shareToken = $this->generateShareToken($deck);

        return $this->render('pages/revisions/share.html.twig', [
            'deck' => $deck,
            'shareToken' => $shareToken,
            'flashcardsCount' => count($deck->getFlashcards()),
        ]);
    }

    // ⚠️ REMOVED: Duplicate import route - handled by QrCodeController
    public function importDeck(
        string $token,
        DeckRepository $deckRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $deckId = $this->decodeShareToken($token);

        if (!$deckId) {
            $this->addFlash('error', '❌ Token de partage invalide ou expiré.');
            return $this->redirectToRoute('app_revisions');
        }

        $originalDeck = $deckRepository->find($deckId);

        if (!$originalDeck) {
            $this->addFlash('error', '❌ Deck introuvable.');
            return $this->redirectToRoute('app_revisions');
        }

        if ($request->isMethod('GET')) {
            return $this->render('pages/revisions/import.html.twig', [
                'deck' => $originalDeck,
                'flashcardsCount' => count($originalDeck->getFlashcards()),
                'token' => $token,
            ]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('import_deck', $request->request->get('_token'))) {
                $this->addFlash('error', '❌ Token CSRF invalide.');
                return $this->redirectToRoute('app_qrcode_import', ['token' => $token]);
            }

            $newDeck = new Deck();
            $newDeck->setUser($this->getUser());
            $newDeck->setTitre($originalDeck->getTitre() . ' (Copie)');
            $newDeck->setDescription($originalDeck->getDescription());
            $newDeck->setCategorie($originalDeck->getCategorie());
            $newDeck->setImage($originalDeck->getImage());
            $newDeck->setPdf($originalDeck->getPdf());
            $newDeck->setDateCreation(new \DateTime());
            $newDeck->setEmoji($originalDeck->getEmoji());
            $newDeck->setMatiere($originalDeck->getMatiere());
            $newDeck->setNiveau($originalDeck->getNiveau());

            $em->persist($newDeck);

            foreach ($originalDeck->getFlashcards() as $originalFlashcard) {
                $newFlashcard = new Flashcard();
                $newFlashcard->setDeck($newDeck);
                $newFlashcard->setCreatedBy($this->getUser());
                $newFlashcard->setTitre($originalFlashcard->getTitre());
                $newFlashcard->setQuestion($originalFlashcard->getQuestion());
                $newFlashcard->setReponse($originalFlashcard->getReponse());
                $newFlashcard->setDescription($originalFlashcard->getDescription());
                $newFlashcard->setImage($originalFlashcard->getImage());
                $newFlashcard->setPdf($originalFlashcard->getPdf());
                $newFlashcard->setNiveauDifficulte($originalFlashcard->getNiveauDifficulte());
                $newFlashcard->setEtat($originalFlashcard->getEtat());

                $em->persist($newFlashcard);
            }

            $em->flush();

            $count = count($originalDeck->getFlashcards());
            $this->addFlash('success', sprintf(
                '✅ Deck "%s" importé avec succès ! (%d flashcard%s copiée%s)',
                $newDeck->getTitre(),
                $count,
                $count > 1 ? 's' : '',
                $count > 1 ? 's' : ''
            ));

            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $newDeck->getId()]);
        }

        return $this->redirectToRoute('app_revisions');
    }

    #[Route('/api/deck-info/{token}', name: 'app_api_deck_info', methods: ['GET'])]
    public function getDeckInfo(string $token, DeckRepository $deckRepository): JsonResponse
    {
        $deckId = $this->decodeShareToken($token);

        if (!$deckId) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        $deck = $deckRepository->find($deckId);

        if (!$deck) {
            return $this->json(['error' => 'Deck introuvable'], 404);
        }

        return $this->json([
            'id' => $deck->getId(),
            'titre' => $deck->getTitre(),
            'description' => $deck->getDescription(),
            'categorie' => $deck->getCategorie(),
            'matiere' => $deck->getMatiere(),
            'niveau' => $deck->getNiveau(),
            'flashcardsCount' => count($deck->getFlashcards()),
            'createdAt' => $deck->getDateCreation()?->format('Y-m-d H:i:s'),
            'author' => $deck->getUser()?->getEmail(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 🤖 CORRECTION AUTOMATIQUE D'UNE CARTE
    // ─────────────────────────────────────────────────────────────

    #[Route('/flashcard/{id}/correct', name: 'app_flashcard_correct', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function correctCard(
        Flashcard $flashcard,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $questionCorrigee = trim($data['question_corrigee'] ?? '');
        $reponseCorrigee  = trim($data['reponse_corrigee'] ?? '');

        if (empty($questionCorrigee) || empty($reponseCorrigee)) {
            return $this->json(['error' => 'Question et réponse corrigées requises.'], 400);
        }

        $flashcard->setQuestion($questionCorrigee);
        $flashcard->setReponse($reponseCorrigee);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => '✅ Carte corrigée avec succès !',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // TRANSLATE FLASHCARD
    // ─────────────────────────────────────────────────────────────

    #[Route('/flashcard/{id}/translate', name: 'app_flashcard_translate', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function translateFlashcard(Flashcard $flashcard): Response
    {
        if ($flashcard->getCreatedBy() !== $this->getUser() &&
            $flashcard->getDeck()->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette flashcard.');
            return $this->redirectToRoute('app_revisions');
        }

        return $this->render('pages/flashcard/translate.html.twig', [
            'flashcard' => $flashcard,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function updateSpacedRepetition(Flashcard $flashcard, int $score): void
    {
        $currentLevel = $flashcard->getNiveauDifficulte() ?? 0;
        if ($score >= 7) {
            $newLevel = min(5, $currentLevel + 1);
        } elseif ($score < 4) {
            $newLevel = max(0, $currentLevel - 1);
        } else {
            $newLevel = $currentLevel;
        }
        $flashcard->setNiveauDifficulte($newLevel);
    }

    private function simulateScore(string $reponseEtudiant, string $bonneReponse): int
    {
        $normalize = fn(string $s): string =>
            mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s));

        $r = $normalize($reponseEtudiant);
        $b = $normalize($bonneReponse);

        similar_text($r, $b, $pct);

        $wordsR = array_filter(explode(' ', $r), fn($w) => strlen($w) > 3);
        $wordsB = array_filter(explode(' ', $b), fn($w) => strlen($w) > 3);
        $common = array_intersect($wordsR, $wordsB);
        $kwScore = count($wordsB) > 0 ? (count($common) / count($wordsB)) * 10 : 0;

        return (int) max(0, min(10, round(($pct / 10) * 0.5 + $kwScore * 0.5)));
    }

    private function simulateFeedback(int $score): string
    {
        return match(true) {
            $score >= 9 => '🏆 Excellent ! Réponse parfaite.',
            $score >= 7 => '✅ Bonne réponse ! Quelques détails à peaufiner.',
            $score >= 5 => '⚠️ Partiellement correct. Révisez les points clés.',
            $score >= 3 => '📖 Réponse incomplète. Relisez attentivement.',
            default     => '❌ Réponse incorrecte. Reprenez depuis le début.',
        };
    }

    private function generateShareToken(Deck $deck): string
    {
        $secret = $this->getParameter('kernel.secret');
        $timestamp = time();
        $data = sprintf('%d:%d:%s', $deck->getId(), $timestamp, $secret);
        return base64_encode($data);
    }

    private function decodeShareToken(string $token): ?int
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);

            if (count($parts) !== 3) {
                return null;
            }

            $deckId = (int) $parts[0];
            $timestamp = (int) $parts[1];
            $providedSecret = $parts[2];

            $expectedSecret = $this->getParameter('kernel.secret');
            if ($providedSecret !== $expectedSecret) {
                return null;
            }

            $maxAge = 30 * 24 * 60 * 60;
            if (time() - $timestamp > $maxAge) {
                return null;
            }

            return $deckId;
        } catch (\Exception $e) {
            return null;
        }
    }
}