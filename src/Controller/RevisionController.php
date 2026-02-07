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

        // Accès libre à tous les decks
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
        $flashcard->setCreatedBy($currentUser); // Associe le créateur

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

        // ⚠️ TEMPORAIRE : Autoriser tout le monde à modifier (développement uniquement)
        // Commenté jusqu'à ce que la migration soit exécutée
        /*
        // Autorisé si admin OU créateur de la flashcard
        if (!$this->isGranted('ROLE_ADMIN') && $flashcard->getCreatedBy()?->getId() !== $currentUser->getId()) {
            $this->addFlash('warning', 'Vous n\'êtes pas autorisé à modifier cette flashcard.');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
        }
        */

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
        $currentUser = $this->getUser();

        // ⚠️ TEMPORAIRE : Autoriser tout le monde à supprimer (développement uniquement)
        // Commenté jusqu'à ce que la migration soit exécutée
        /*
        // Autorisé si admin OU créateur de la flashcard
        if (!$this->isGranted('ROLE_ADMIN') && $flashcard->getCreatedBy()?->getId() !== $currentUser->getId()) {
            $this->addFlash('warning', 'Vous n\'êtes pas autorisé à supprimer cette flashcard.');
            return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
        }
        */

        if ($this->isCsrfTokenValid('delete' . $flashcard->getId(), $request->request->get('_token'))) {
            $em->remove($flashcard);
            $em->flush();
            $this->addFlash('success', 'Flashcard supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_revisions_deck_study', ['id' => $deck->getIdDeck()]);
    }
}