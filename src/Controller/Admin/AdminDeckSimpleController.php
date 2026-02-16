<?php

namespace App\Controller\Admin;

use App\Entity\Deck;
use App\Form\DeckType;
use App\Repository\DeckRepository;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/deck')]
#[IsGranted('ROLE_ADMIN')]
class AdminDeckSimpleController extends AbstractController
{
    #[Route('', name: 'app_admin_deck_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_admin_revision');
    }

    #[Route('/new', name: 'app_admin_deck_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, AuditLogService $auditLog): Response
    {
        $deck = new Deck();
        $deck->setUser($this->getUser());

        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Upload image
            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $deck->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload error: ' . $e->getMessage());
                }
            }

            // Upload PDF
            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $deck->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'PDF upload error: ' . $e->getMessage());
                }
            }

            $deck->setDateCreation(new \DateTime());

            $em->persist($deck);
            $em->flush();

            // Log in audit trail
            try {
                $auditLog->log(
                    'deck_created',
                    'deck',
                    $deck->getId(),
                    sprintf('Created deck: %s', $deck->getTitre())
                );
            } catch (\Exception $e) {
                // Silently continue if audit log fails
            }

            $this->addFlash('success', 'Deck created successfully!');

            return $this->redirectToRoute('app_admin_revision');
        }

        return $this->render('admin/deck/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_deck_edit', methods: ['GET', 'POST'])]
    public function edit(
        Deck $deck,
        Request $request,
        EntityManagerInterface $em,
        AuditLogService $auditLog
    ): Response
    {
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Upload new image (replaces old if uploaded)
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
                    $this->addFlash('error', 'Image upload error: ' . $e->getMessage());
                }
            }

            // Upload new PDF (replaces old if uploaded)
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
                    $this->addFlash('error', 'PDF upload error: ' . $e->getMessage());
                }
            }

            $em->flush();

            // Log in audit trail
            try {
                $auditLog->log(
                    'deck_updated',
                    'deck',
                    $deck->getId(),
                    sprintf('Updated deck: %s', $deck->getTitre())
                );
            } catch (\Exception $e) {
                // Silently continue if audit log fails
            }

            $this->addFlash('success', 'Deck updated successfully!');

            return $this->redirectToRoute('app_admin_revision');
        }

        return $this->render('admin/deck/edit.html.twig', [
            'deck' => $deck,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_deck_delete', methods: ['POST'])]
    public function delete(
        Deck $deck,
        Request $request,
        EntityManagerInterface $em,
        AuditLogService $auditLog
    ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $deck->getId(), $request->request->get('_token'))) {
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
            try {
                $auditLog->log(
                    'deck_deleted',
                    'deck',
                    $deckId,
                    sprintf('Deleted deck: %s', $deckTitle)
                );
            } catch (\Exception $e) {
                // Silently continue if audit log fails
            }

            $this->addFlash('success', 'Deck deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_admin_revision');
    }
}
