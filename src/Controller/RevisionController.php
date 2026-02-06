<?php

namespace App\Controller;

use App\Data\SampleData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/revisions')]
class RevisionController extends AbstractController
{
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
            $this->addFlash('success', 'Deck created successfully!');
            return $this->redirectToRoute('app_revisions');
        }

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

        if (!$deck) {
            throw $this->createNotFoundException('Deck not found');
        }

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
            'deck' => $deck,
        ]);
    }

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
