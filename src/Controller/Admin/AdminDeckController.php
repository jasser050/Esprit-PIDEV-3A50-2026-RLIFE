<?php

namespace App\Controller\Admin;

use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Form\DeckType;
use App\Form\FlashcardType;
use App\Repository\DeckRepository;
use App\Repository\FlashcardRepository;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/decks')]
#[IsGranted('ROLE_ADMIN')]
class AdminDeckController extends AbstractController
{
    #[Route('', name: 'app_admin_decks', methods: ['GET'])]
    public function index(DeckRepository $deckRepository): Response
    {
        $decks = $deckRepository->findBy([], ['dateCreation' => 'DESC']);

        // Calculate stats
        $totalDecks = count($decks);
        $totalFlashcards = 0;
        foreach ($decks as $deck) {
            $totalFlashcards += $deck->getFlashcards()->count();
        }

        return $this->render('admin/decks/index.html.twig', [
            'decks' => $decks,
            'total_decks' => $totalDecks,
            'total_flashcards' => $totalFlashcards,
        ]);
    }

    #[Route('/new', name: 'app_admin_deck_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, AuditLogService $auditLog): Response
    {
        $deck = new Deck();
        // Admin creates deck without specific user - will be available to all
        $deck->setUser($this->getUser());

        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Handle image upload
            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $deck->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image: ' . $e->getMessage());
                }
            }

            // Handle PDF upload
            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $deck->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading PDF: ' . $e->getMessage());
                }
            }

            $deck->setDateCreation(new \DateTime());
            $em->persist($deck);
            $em->flush();

            // Log in audit trail
            $auditLog->log(
                'deck_created',
                'deck',
                $deck->getId(),
                sprintf('Created deck: %s', $deck->getTitre())
            );

            $this->addFlash('success', sprintf('Deck "%s" created successfully!', $deck->getTitre()));
            return $this->redirectToRoute('app_admin_decks');
        }

        return $this->render('admin/decks/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_deck_edit', methods: ['GET', 'POST'])]
    public function edit(Deck $deck, Request $request, EntityManagerInterface $em, AuditLogService $auditLog): Response
    {
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Handle image upload
            if ($imageFile = $form->get('imageFile')->getData()) {
                // Delete old image
                if ($deck->getImage()) {
                    $oldImage = $uploadDir . $deck->getImage();
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
                
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $deck->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image: ' . $e->getMessage());
                }
            }

            // Handle PDF upload
            if ($pdfFile = $form->get('pdfFile')->getData()) {
                // Delete old PDF
                if ($deck->getPdf()) {
                    $oldPdf = $uploadDir . $deck->getPdf();
                    if (file_exists($oldPdf)) {
                        unlink($oldPdf);
                    }
                }
                
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $deck->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading PDF: ' . $e->getMessage());
                }
            }

            $em->flush();

            // Log in audit trail
            $auditLog->log(
                'deck_updated',
                'deck',
                $deck->getId(),
                sprintf('Updated deck: %s', $deck->getTitre())
            );

            $this->addFlash('success', sprintf('Deck "%s" updated successfully!', $deck->getTitre()));
            return $this->redirectToRoute('app_admin_decks');
        }

        return $this->render('admin/decks/edit.html.twig', [
            'deck' => $deck,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_deck_view', methods: ['GET'])]
    public function view(Deck $deck): Response
    {
        return $this->render('admin/decks/view.html.twig', [
            'deck' => $deck,
            'flashcards' => $deck->getFlashcards(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_deck_delete', methods: ['POST'])]
    public function delete(Deck $deck, EntityManagerInterface $em, AuditLogService $auditLog): Response
    {
        $deckTitle = $deck->getTitre();
        $deckId = $deck->getId();
        
        // Delete associated files
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';
        
        if ($deck->getImage()) {
            $imagePath = $uploadDir . $deck->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if ($deck->getPdf()) {
            $pdfPath = $uploadDir . $deck->getPdf();
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }

        $em->remove($deck);
        $em->flush();

        // Log in audit trail
        $auditLog->log(
            'deck_deleted',
            'deck',
            $deckId,
            sprintf('Deleted deck: %s', $deckTitle)
        );

        $this->addFlash('success', sprintf('Deck "%s" deleted successfully!', $deckTitle));
        return $this->redirectToRoute('app_admin_decks');
    }

    // Flashcard management within decks
    #[Route('/{id}/flashcards/new', name: 'app_admin_flashcard_new', methods: ['GET', 'POST'])]
    public function newFlashcard(Deck $deck, Request $request, EntityManagerInterface $em, AuditLogService $auditLog): Response
    {
        $flashcard = new Flashcard();
        $flashcard->setDeck($deck);

        $form = $this->createForm(FlashcardType::class, $flashcard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/flashcards/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Handle image upload
            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $flashcard->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image: ' . $e->getMessage());
                }
            }

            // Handle PDF upload
            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $flashcard->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading PDF: ' . $e->getMessage());
                }
            }

            $em->persist($flashcard);
            $em->flush();

            // Log in audit trail
            $auditLog->log(
                'flashcard_created',
                'flashcard',
                $flashcard->getId(),
                sprintf('Created flashcard in deck: %s', $deck->getTitre())
            );

            $this->addFlash('success', 'Flashcard added successfully!');
            return $this->redirectToRoute('app_admin_deck_view', ['id' => $deck->getId()]);
        }

        return $this->render('admin/decks/flashcard_new.html.twig', [
            'deck' => $deck,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/flashcards/{id}/delete', name: 'app_admin_flashcard_delete', methods: ['POST'])]
    public function deleteFlashcard(Flashcard $flashcard, EntityManagerInterface $em, AuditLogService $auditLog): Response
    {
        $deckId = $flashcard->getDeck()->getId();
        
        // Delete associated files
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/flashcards/';
        
        if ($flashcard->getImage()) {
            $imagePath = $uploadDir . $flashcard->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if ($flashcard->getPdf()) {
            $pdfPath = $uploadDir . $flashcard->getPdf();
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }

        $em->remove($flashcard);
        $em->flush();

        // Log in audit trail
        $auditLog->log(
            'flashcard_deleted',
            'flashcard',
            $flashcard->getId(),
            'Deleted flashcard'
        );

        $this->addFlash('success', 'Flashcard deleted successfully!');
        return $this->redirectToRoute('app_admin_deck_view', ['id' => $deckId]);
    }
}
