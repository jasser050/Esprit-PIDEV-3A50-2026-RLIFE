<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Form\DeckType;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/deck')]
#[IsGranted('ROLE_ADMIN')]
class AdminDeckController extends AbstractController
{
    #[Route('', name: 'app_admin_deck_index', methods: ['GET'])]
    public function index(): Response
    {
        // Redirige vers la page principale de révision (liste + formulaire intégré)
        return $this->redirectToRoute('app_admin_revision');
    }

    #[Route('/new', name: 'app_admin_deck_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $deck = new Deck();
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $filename);
                $deck->setImage($filename);
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                $pdfFile->move($uploadDir, $filename);
                $deck->setPdf($filename);
            }

            $deck->setUser($this->getUser());
            $deck->setDateCreation(new \DateTime());

            $em->persist($deck);
            $em->flush();

            $this->addFlash('success', 'Deck créé avec succès.');

            return $this->redirectToRoute('app_admin_revision');
        }

        return $this->render('admin/deck/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Édition d'un deck
     */
    #[Route('/{id_deck}/edit', name: 'app_admin_deck_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'id_deck')] Deck $deck,
        EntityManagerInterface $em
    ): Response
    {
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $filename);
                $deck->setImage($filename);
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                $pdfFile->move($uploadDir, $filename);
                $deck->setPdf($filename);
            }

            $em->flush();

            $this->addFlash('success', 'Deck modifié avec succès.');

            return $this->redirectToRoute('app_admin_revision');
        }

        return $this->render('admin/deck/edit.html.twig', [
            'deck' => $deck,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un deck
     */
    #[Route('/{id_deck}', name: 'app_admin_deck_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'id_deck')] Deck $deck,
        EntityManagerInterface $em
    ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $deck->getId(), $request->request->get('_token'))) {
            $em->remove($deck);
            $em->flush();

            $this->addFlash('success', 'Deck supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_revision');
    }
}