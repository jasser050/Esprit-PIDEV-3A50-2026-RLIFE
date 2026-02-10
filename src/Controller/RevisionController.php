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

    #[Route('/deck/{id}', name: 'app_revisions_deck_study', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function study(Deck $deck): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_revisions');
        }

        // Admin voit tout
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('pages/revisions/study.html.twig', [
                'deck'       => $deck,
                'flashcards' => $deck->getFlashcards(),
            ]);
        }

        // Accès libre à tous les decks (selon ta logique actuelle)
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
            throw $this->createNotFoundException('Deck introuvable');
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
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
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
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
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

        if ($this->isCsrfTokenValid('delete' . $flashcard->getId(), $request->request->get('_token'))) {
            $em->remove($flashcard);
            $em->flush();
            $this->addFlash('success', 'Flashcard supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
    }

    /**
     * Génère automatiquement des flashcards via l'API Gemini (Google AI)
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
            $this->addFlash('error', 'Veuillez entrer un thème ou un sujet.');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;

        if (!$apiKey) {
            $this->addFlash('error', 'Clé API Gemini non configurée dans .env');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
        }

        try {
            // ✅ CORRECTION MAJEURE : 
            // 1. Utiliser l'API v1 (pas v1beta)
            // 2. Utiliser le modèle gemini-2.5-flash
            // 3. Passer la clé API dans le HEADER x-goog-api-key (pas dans l'URL)
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
                                        'text' => "Génère 6 flashcards éducatives en français sur le thème : \"$theme\". 
                                        Retourne UNIQUEMENT un tableau JSON valide (pas de markdown, pas de ```, pas de texte avant/après).
                                        Format exact attendu pour CHAQUE élément :
                                        {
                                          \"titre\": \"Titre court et clair\",
                                          \"question\": \"Question pédagogique précise\",
                                          \"reponse\": \"Réponse complète et correcte\",
                                          \"description\": \"Explication ou contexte (ou vide si inutile)\",
                                          \"niveauDifficulte\": entier entre 1 et 5,
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

            // Nettoyage très agressif (Gemini aime bien ajouter du markdown)
            $text = trim($text);
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $flashcardsData = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($flashcardsData)) {
                throw new \Exception('Réponse JSON invalide de Gemini (' . json_last_error_msg() . '). Réponse brute : ' . substr($text, 0, 200));
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
                $flashcard->setTitre($item['titre'] ?? 'Flashcard générée par IA');
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
                $this->addFlash('success', $count . ' flashcards générées avec succès via IA !');
            } else {
                $this->addFlash('warning', 'Aucune flashcard valide n\'a pu être créée.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur génération IA : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
    }
}