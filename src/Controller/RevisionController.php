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
        if ($this->isGranted('ROLE_ADMIN')) {
            $decks = $deckRepository->findAll();
        } else {
            $decks = $deckRepository->findBy(
                ['user' => $this->getUser()],
                ['dateCreation' => 'DESC']
            );
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

            $this->addFlash('success', 'Deck créé !');
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
        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($deck->getUser() !== $this->getUser()) {
                $this->addFlash('warning', 'Ce deck ne t\'appartient pas.');
                return $this->redirectToRoute('app_revisions');
            }
        }

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

        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($deck->getUser() !== $this->getUser()) {
                $this->addFlash('warning', 'Ce deck ne t\'appartient pas.');
                return $this->redirectToRoute('app_revisions');
            }
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
                    $this->addFlash('error', 'Erreur image');
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $flashcard->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur PDF');
                }
            }

            $em->persist($flashcard);
            $em->flush();

            $this->addFlash('success', 'Flashcard ajoutée !');
            return $this->redirectToRoute('app_revisions');
        }

        return $this->render('pages/flashcard/new.html.twig', [
            'form' => $form->createView(),
            'deck' => $deck,
        ]);
    }
}