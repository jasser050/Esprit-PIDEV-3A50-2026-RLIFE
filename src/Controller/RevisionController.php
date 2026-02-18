<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Form\DeckType;
use App\Form\FlashcardType;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/revisions')]
class RevisionController extends AbstractController
{
    #[Route('', name: 'app_revisions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(DeckRepository $deckRepository): Response
    {
        $decks = $deckRepository->findAll();

        foreach ($decks as $deck) {
            $flashcards = $deck->getFlashcards();
            $cardCount = count($flashcards);
            $masteredCount = 0;
            
            foreach ($flashcards as $flashcard) {
                if ($flashcard->getEtat() === 'maitrisee') {
                    $masteredCount++;
                }
            }
            
            $deck->setCardCount($cardCount);
            $deck->setMasteredCount($masteredCount);
        }

        return $this->render('pages/revisions/index.html.twig', [
            'decks' => $decks,
        ]);
    }

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
                    $this->addFlash('error', 'Image upload error');
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $deck->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'PDF upload error');
                }
            }

            $deck->setDateCreation(new \DateTime());
            $em->persist($deck);
            $em->flush();

            $this->addFlash('success', 'Deck created successfully!');
            return $this->redirectToRoute('app_revisions');
        }

        return $this->render('pages/revisions/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/deck/{id}', name: 'app_revisions_deck_study', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function study(Deck $deck): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('app_revisions');
        }

        // Admin can see all decks
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('pages/revisions/study.html.twig', [
                'deck'       => $deck,
                'flashcards' => $deck->getFlashcards(),
            ]);
        }

        // Users can access all decks (open access)
        return $this->render('pages/revisions/study.html.twig', [
            'deck'       => $deck,
            'flashcards' => $deck->getFlashcards(),
        ]);
    }

    #[Route('/flashcard/new/{deck_id}', name: 'app_flashcard_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newFlashcard(
        int $deck_id,
        DeckRepository $deckRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $deck = $deckRepository->find($deck_id);

        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

        $currentUser = $this->getUser();

        $flashcard = new Flashcard();
        $flashcard->setDeck($deck);
        $flashcard->setCreatedBy($currentUser);

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
                    $this->addFlash('error', 'Image upload error');
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $flashcard->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'PDF upload error');
                }
            }

            $em->persist($flashcard);
            $em->flush();

            $this->addFlash('success', 'Flashcard added successfully!');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
        }

        return $this->render('pages/flashcard/new.html.twig', [
            'form' => $form->createView(),
            'deck' => $deck,
        ]);
    }

    #[Route('/flashcard/{id}/edit', name: 'app_flashcard_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editFlashcard(
        Flashcard $flashcard,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $deck = $flashcard->getDeck();
        $currentUser = $this->getUser();

        // Temporarily allow everyone to edit (for development)
        // TODO: Add proper authorization check

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
                    $this->addFlash('error', 'Image upload error');
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $flashcard->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'PDF upload error');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Flashcard updated successfully!');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
        }

        return $this->render('pages/flashcard/edit.html.twig', [
            'form' => $form->createView(),
            'flashcard' => $flashcard,
            'deck' => $deck,
        ]);
    }

    #[Route('/flashcard/{id}', name: 'app_flashcard_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteFlashcard(
        Flashcard $flashcard,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $deck = $flashcard->getDeck();

        // Temporarily allow everyone to delete (for development)
        // TODO: Add proper authorization check

        if ($this->isCsrfTokenValid('delete' . $flashcard->getId(), $request->request->get('_token'))) {
            $em->remove($flashcard);
            $em->flush();
            $this->addFlash('success', 'Flashcard deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
    }

    /**
     * Automatically generates flashcards via the Gemini API (Google AI)
     */
    #[Route('/deck/{id}/generate-flashcards', name: 'app_deck_generate_flashcards', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateFlashcards(
        Deck $deck,
        Request $request,
        EntityManagerInterface $em,
        HttpClientInterface $httpClient
    ): Response
    {
        $theme = trim($request->request->get('theme') ?? '');

        if (empty($theme)) {
            $this->addFlash('error', 'Please enter a theme or subject.');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;

        if (!$apiKey) {
            $this->addFlash('error', 'Gemini API key not configured in .env');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
        }

        try {
            // Using Gemini 2.5 Flash model with proper API v1 endpoint
            // API key passed in header x-goog-api-key (not in URL)
            $response = $httpClient->request(
                'POST', 
                'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent',
                [
                    'headers' => [
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => "Generate 6 educational flashcards in English on the theme: \"$theme\". 
                                        Return ONLY a valid JSON array (no markdown, no ```, no text before/after).
                                        Exact format expected for EACH element:
                                        {
                                          \"titre\": \"Short and clear title\",
                                          \"question\": \"Precise pedagogical question\",
                                          \"reponse\": \"Complete and correct answer\",
                                          \"description\": \"Explanation or context (or empty if unnecessary)\",
                                          \"niveauDifficulte\": integer between 1 and 5,
                                          \"etat\": \"actif\"
                                        }"
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'maxOutputTokens' => 2048,
                        ]
                    ]
                ]
            );

            $data = $response->toArray();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Aggressive cleanup (Gemini likes to add markdown)
            $text = trim($text);
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $flashcardsData = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($flashcardsData)) {
                throw new \Exception('Invalid JSON response from Gemini (' . json_last_error_msg() . '). Raw response: ' . substr($text, 0, 200));
            }

            $currentUser = $this->getUser();
            $count = 0;

            foreach ($flashcardsData as $item) {
                if (empty($item['question']) || empty($item['reponse'])) {
                    continue;
                }

                $flashcard = new Flashcard();
                $flashcard->setDeck($deck);
                $flashcard->setCreatedBy($currentUser);
                $flashcard->setTitre($item['titre'] ?? 'AI-generated Flashcard');
                $flashcard->setQuestion($item['question']);
                $flashcard->setReponse($item['reponse']);
                $flashcard->setDescription($item['description'] ?? null);
                $flashcard->setNiveauDifficulte($item['niveauDifficulte'] ?? 3);
                $flashcard->setEtat($item['etat'] ?? 'actif');

                $em->persist($flashcard);
                $count++;
            }

            if ($count > 0) {
                $em->flush();
                $this->addFlash('success', $count . ' flashcards generated successfully via AI!');
            } else {
                $this->addFlash('warning', 'No valid flashcards could be created.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'AI generation error: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getId()]);
    }
}
