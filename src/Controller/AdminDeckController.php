<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Form\DeckType;
use App\Repository\DeckRepository;
use App\Repository\FlashcardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/deck')]
#[IsGranted('ROLE_ADMIN')]
class AdminDeckController extends AbstractController
{
    #[Route('', name: 'app_admin_deck_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_admin_revision');
    }

    #[Route('/new', name: 'app_admin_deck_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $deck = new Deck();
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // ✅ VALIDATION EXPLICITE CÔTÉ SERVEUR
            
            // 1. Vérifier si le formulaire est valide
            if (!$form->isValid()) {
                // Récupérer toutes les erreurs du formulaire
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                
                // Ajouter un message flash général
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez corriger les champs marqués en rouge.');
                
                // Retourner le formulaire avec les erreurs
                return $this->render('admin/deck/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // 2. Validation supplémentaire de l'entité Deck
            $violations = $validator->validate($deck);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
                
                return $this->render('admin/deck/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // 3. Vérifier que les fichiers sont bien uploadés
            $imageFile = $form->get('imageFile')->getData();
            $pdfFile = $form->get('pdfFile')->getData();

            if (!$imageFile) {
                $this->addFlash('error', 'L\'image de couverture est obligatoire.');
                return $this->render('admin/deck/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if (!$pdfFile) {
                $this->addFlash('error', 'Le fichier PDF est obligatoire.');
                return $this->render('admin/deck/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // 4. Traitement des uploads
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            // Créer le dossier s'il n'existe pas
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            try {
                // Upload image
                $imageFilename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $imageFilename);
                $deck->setImage($imageFilename);

                // Upload PDF
                $pdfFilename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                $pdfFile->move($uploadDir, $pdfFilename);
                $deck->setPdf($pdfFilename);

            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload des fichiers : ' . $e->getMessage());
                return $this->render('admin/deck/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // 5. Enregistrement en base de données
            try {
                $deck->setUser($this->getUser());
                $deck->setDateCreation(new \DateTime());

                $em->persist($deck);
                $em->flush();

                $this->addFlash('success', '✓ Deck créé avec succès !');
                return $this->redirectToRoute('app_admin_revision');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du deck : ' . $e->getMessage());
                return $this->render('admin/deck/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        // Affichage du formulaire vide (GET)
        return $this->render('admin/deck/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * ✨ NOUVELLE MÉTHODE : Afficher un deck avec ses flashcards
     */
    #[Route('/{id_deck}/show', name: 'app_admin_deck_show', methods: ['GET'])]
    public function show(
        #[MapEntity(id: 'id_deck')] Deck $deck,
        FlashcardRepository $flashcardRepository
    ): Response
    {
        $flashcards = $flashcardRepository->findBy(['deck' => $deck]);

        return $this->render('admin/deck/show.html.twig', [
            'deck' => $deck,
            'flashcards' => $flashcards,
        ]);
    }

    #[Route('/{id_deck}/edit', name: 'app_admin_deck_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'id_deck')] Deck $deck,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response
    {
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // ✅ VALIDATION CÔTÉ SERVEUR
            
            if (!$form->isValid()) {
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez corriger les champs marqués en rouge.');
                
                return $this->render('admin/deck/edit.html.twig', [
                    'deck' => $deck,
                    'form' => $form->createView(),
                ]);
            }

            // Validation de l'entité
            $violations = $validator->validate($deck);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
                
                return $this->render('admin/deck/edit.html.twig', [
                    'deck' => $deck,
                    'form' => $form->createView(),
                ]);
            }

            // Traitement des fichiers (optionnels en édition)
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            try {
                // Upload nouvelle image (si fournie)
                if ($imageFile = $form->get('imageFile')->getData()) {
                    $imageFilename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                    $imageFile->move($uploadDir, $imageFilename);
                    $deck->setImage($imageFilename);
                }

                // Upload nouveau PDF (si fourni)
                if ($pdfFile = $form->get('pdfFile')->getData()) {
                    $pdfFilename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                    $pdfFile->move($uploadDir, $pdfFilename);
                    $deck->setPdf($pdfFilename);
                }

            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload des fichiers : ' . $e->getMessage());
                return $this->render('admin/deck/edit.html.twig', [
                    'deck' => $deck,
                    'form' => $form->createView(),
                ]);
            }

            // Enregistrement
            try {
                $em->flush();
                $this->addFlash('success', '✓ Deck modifié avec succès !');
                return $this->redirectToRoute('app_admin_revision');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
                return $this->render('admin/deck/edit.html.twig', [
                    'deck' => $deck,
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('admin/deck/edit.html.twig', [
            'deck' => $deck,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_deck}', name: 'app_admin_deck_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'id_deck')] Deck $deck,
        EntityManagerInterface $em
    ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $deck->getIdDeck(), $request->request->get('_token'))) {
            try {
                $em->remove($deck);
                $em->flush();
                $this->addFlash('success', '✓ Deck supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_admin_revision');
    }
}
