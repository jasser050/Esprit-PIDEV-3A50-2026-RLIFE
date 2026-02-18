<?php

namespace App\Controller;

<<<<<<< HEAD
use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Form\DeckType;
use App\Form\FlashcardType;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
=======
use App\Data\SampleData;
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
<<<<<<< HEAD
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
=======
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd

#[Route('/revisions')]
class RevisionController extends AbstractController
{
<<<<<<< HEAD
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

=======
    #[Route('', name: 'app_revisions')]
    public function index(): Response
    {
        return $this->render('pages/revisions/index.html.twig', [
            'decks' => SampleData::getFlashcardDecks(),
        ]);
    }

    #[Route('/deck/new', name: 'app_revisions_deck_new', methods: ['GET', 'POST'])]
    public function deckNew(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // In a real app, we would save the deck to the database
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
            $this->addFlash('success', 'Deck created successfully!');
            return $this->redirectToRoute('app_revisions');
        }

<<<<<<< HEAD
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

=======
        return $this->render('pages/revisions/deck_new.html.twig', [
            'courses' => SampleData::getCourses(),
        ]);
    }

    #[Route('/deck/{id}', name: 'app_revisions_deck')]
    public function deck(int $id): Response
    {
        $decks = SampleData::getFlashcardDecks();
        $deck = null;
        foreach ($decks as $d) {
            if ($d['id'] === $id) {
                $deck = $d;
                break;
            }
        }

>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

<<<<<<< HEAD
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
=======
        $cards = array_filter(SampleData::getFlashcards(), fn($c) => $c['deck_id'] === $id);

        return $this->render('pages/revisions/deck.html.twig', [
            'deck' => $deck,
            'cards' => array_values($cards),
        ]);
    }

    #[Route('/deck/{id}/edit', name: 'app_revisions_deck_edit', methods: ['GET', 'POST'])]
    public function deckEdit(Request $request, int $id): Response
    {
        $decks = SampleData::getFlashcardDecks();
        $deck = null;
        foreach ($decks as $d) {
            if ($d['id'] === $id) {
                $deck = $d;
                break;
            }
        }

        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

        if ($request->isMethod('POST')) {
            // In a real app, we would update the deck in the database
            $this->addFlash('success', 'Deck updated successfully!');
            return $this->redirectToRoute('app_revisions_deck', ['id' => $id]);
        }

        return $this->render('pages/revisions/deck_edit.html.twig', [
            'deck' => $deck,
            'courses' => SampleData::getCourses(),
        ]);
    }

    #[Route('/deck/{id}/delete', name: 'app_revisions_deck_delete', methods: ['POST'])]
    public function deckDelete(int $id): Response
    {
        $decks = SampleData::getFlashcardDecks();
        $deck = null;
        foreach ($decks as $d) {
            if ($d['id'] === $id) {
                $deck = $d;
                break;
            }
        }

        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

        // In a real app, we would delete the deck from the database
        $this->addFlash('success', 'Deck deleted successfully!');
        return $this->redirectToRoute('app_revisions');
    }

    #[Route('/deck/{deckId}/card/new', name: 'app_revisions_card_new', methods: ['GET', 'POST'])]
    public function cardNew(Request $request, int $deckId): Response
    {
        $decks = SampleData::getFlashcardDecks();
        $deck = null;
        foreach ($decks as $d) {
            if ($d['id'] === $deckId) {
                $deck = $d;
                break;
            }
        }

        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

        if ($request->isMethod('POST')) {
            // In a real app, we would save the card to the database
            $this->addFlash('success', 'Card created successfully!');
            return $this->redirectToRoute('app_revisions_deck', ['id' => $deckId]);
        }

        return $this->render('pages/revisions/card_new.html.twig', [
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
            'deck' => $deck,
        ]);
    }

<<<<<<< HEAD
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
=======
    #[Route('/deck/{deckId}/card/{cardId}/edit', name: 'app_revisions_card_edit', methods: ['GET', 'POST'])]
    public function cardEdit(Request $request, int $deckId, int $cardId): Response
    {
        $decks = SampleData::getFlashcardDecks();
        $deck = null;
        foreach ($decks as $d) {
            if ($d['id'] === $deckId) {
                $deck = $d;
                break;
            }
        }

        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

        $cards = SampleData::getFlashcards();
        $card = null;
        foreach ($cards as $c) {
            if ($c['id'] === $cardId && $c['deck_id'] === $deckId) {
                $card = $c;
                break;
            }
        }

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        if ($request->isMethod('POST')) {
            // In a real app, we would update the card in the database
            $this->addFlash('success', 'Card updated successfully!');
            return $this->redirectToRoute('app_revisions_deck', ['id' => $deckId]);
        }

        return $this->render('pages/revisions/card_edit.html.twig', [
            'deck' => $deck,
            'card' => $card,
        ]);
    }

    #[Route('/deck/{deckId}/card/{cardId}/delete', name: 'app_revisions_card_delete', methods: ['POST'])]
    public function cardDelete(int $deckId, int $cardId): Response
    {
        $decks = SampleData::getFlashcardDecks();
        $deck = null;
        foreach ($decks as $d) {
            if ($d['id'] === $deckId) {
                $deck = $d;
                break;
            }
        }

        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

        $cards = SampleData::getFlashcards();
        $card = null;
        foreach ($cards as $c) {
            if ($c['id'] === $cardId && $c['deck_id'] === $deckId) {
                $card = $c;
                break;
            }
        }

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        // In a real app, we would delete the card from the database
        $this->addFlash('success', 'Card deleted successfully!');
        return $this->redirectToRoute('app_revisions_deck', ['id' => $deckId]);
    }
}
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
