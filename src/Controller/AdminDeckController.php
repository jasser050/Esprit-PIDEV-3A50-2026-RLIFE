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
use Symfony\Component\HttpFoundation\JsonResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/deck')]
#[IsGranted('ROLE_ADMIN')]
class AdminDeckController extends AbstractController
{
    #[Route('', name: 'app_admin_revision', methods: ['GET'])]
    public function index(Request $request, DeckRepository $deckRepository): Response
    {
        // Récupération des paramètres de recherche et filtres
        $search = $request->query->get('search', '');
        $subject = $request->query->get('subject', 'all');
        $level = $request->query->get('level', 'all');
        $dateFrom = $request->query->get('dateFrom', '');
        $dateTo = $request->query->get('dateTo', '');
        $progress = $request->query->get('progress', 'all');
        $sort = $request->query->get('sort', 'date-desc');

        // Construction de la requête avec QueryBuilder
        $qb = $deckRepository->createQueryBuilder('d')
            ->where('d.user = :user')
            ->setParameter('user', $this->getUser());

        // Filtrage par recherche textuelle
        if (!empty($search)) {
            $qb->andWhere('LOWER(d.titre) LIKE :search 
                       OR LOWER(d.matiere) LIKE :search 
                       OR LOWER(d.niveau) LIKE :search 
                       OR LOWER(d.description) LIKE :search')
               ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Filtrage par matière
        if ($subject !== 'all') {
            $qb->andWhere('LOWER(d.matiere) = :subject')
               ->setParameter('subject', strtolower($subject));
        }

        // Filtrage par niveau
        if ($level !== 'all') {
            $qb->andWhere('LOWER(d.niveau) = :level')
               ->setParameter('level', strtolower($level));
        }

        // Filtrage par date
        if (!empty($dateFrom)) {
            $qb->andWhere('d.dateCreation >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if (!empty($dateTo)) {
            $qb->andWhere('d.dateCreation <= :dateTo')
               ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        // Tri
        switch ($sort) {
            case 'date':
                $qb->orderBy('d.dateCreation', 'ASC');
                break;
            case 'date-desc':
                $qb->orderBy('d.dateCreation', 'DESC');
                break;
            case 'title':
                $qb->orderBy('d.titre', 'ASC');
                break;
            case 'title-desc':
                $qb->orderBy('d.titre', 'DESC');
                break;
            case 'cards':
                // Pour trier par nombre de cartes, on doit joindre les flashcards
                $qb->leftJoin('d.flashcards', 'f')
                   ->groupBy('d.idDeck')
                   ->orderBy('COUNT(f.idFlashcard)', 'ASC');
                break;
            default:
                $qb->orderBy('d.dateCreation', 'DESC');
        }

        $decks = $qb->getQuery()->getResult();

        // Si filtrage par progression (nécessite un traitement PHP car c'est calculé)
        if ($progress !== 'all') {
            $decks = array_filter($decks, function($deck) use ($progress) {
                // Ici vous devrez calculer la progression réelle
                // Pour l'exemple, on utilise des données fictives
                $deckProgress = rand(0, 100); // À remplacer par votre logique
                
                if ($progress === '100') {
                    return $deckProgress == 100;
                }
                
                [$min, $max] = explode('-', $progress);
                return $deckProgress >= (int)$min && $deckProgress <= (int)$max;
            });
        }

        return $this->render('admin/revision.html.twig', [
            'decks' => $decks,
            'currentFilters' => [
                'search' => $search,
                'subject' => $subject,
                'level' => $level,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'progress' => $progress,
                'sort' => $sort,
            ]
        ]);
    }

    /**
     * Export PDF des decks
     */
    #[Route('/export-pdf', name: 'app_admin_deck_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, DeckRepository $deckRepository): Response
    {
        // Récupération des mêmes filtres que l'index
        $search = $request->query->get('search', '');
        $subject = $request->query->get('subject', 'all');
        $level = $request->query->get('level', 'all');
        $dateFrom = $request->query->get('dateFrom', '');
        $dateTo = $request->query->get('dateTo', '');
        $sort = $request->query->get('sort', 'date-desc');

        // Même logique de filtrage que l'index
        $qb = $deckRepository->createQueryBuilder('d')
            ->where('d.user = :user')
            ->setParameter('user', $this->getUser());

        if (!empty($search)) {
            $qb->andWhere('LOWER(d.titre) LIKE :search 
                       OR LOWER(d.matiere) LIKE :search 
                       OR LOWER(d.niveau) LIKE :search 
                       OR LOWER(d.description) LIKE :search')
               ->setParameter('search', '%' . strtolower($search) . '%');
        }

        if ($subject !== 'all') {
            $qb->andWhere('LOWER(d.matiere) = :subject')
               ->setParameter('subject', strtolower($subject));
        }

        if ($level !== 'all') {
            $qb->andWhere('LOWER(d.niveau) = :level')
               ->setParameter('level', strtolower($level));
        }

        if (!empty($dateFrom)) {
            $qb->andWhere('d.dateCreation >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if (!empty($dateTo)) {
            $qb->andWhere('d.dateCreation <= :dateTo')
               ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        // Tri
        switch ($sort) {
            case 'date':
                $qb->orderBy('d.dateCreation', 'ASC');
                break;
            case 'date-desc':
                $qb->orderBy('d.dateCreation', 'DESC');
                break;
            case 'title':
                $qb->orderBy('d.titre', 'ASC');
                break;
            case 'title-desc':
                $qb->orderBy('d.titre', 'DESC');
                break;
            default:
                $qb->orderBy('d.dateCreation', 'DESC');
        }

        $decks = $qb->getQuery()->getResult();

        // Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->setIsHtml5ParserEnabled(true);
        $pdfOptions->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($pdfOptions);

        // Génération du HTML pour le PDF
        $html = $this->renderView('admin/deck/pdf_export.html.twig', [
            'decks' => $decks,
            'date' => new \DateTime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Génération du nom de fichier
        $filename = sprintf('mes-decks-%s.pdf', (new \DateTime())->format('Y-m-d'));

        // Retour de la réponse PDF
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    /**
     * API JSON pour recherche AJAX (optionnel)
     */
    #[Route('/search-ajax', name: 'app_admin_deck_search_ajax', methods: ['GET'])]
    public function searchAjax(Request $request, DeckRepository $deckRepository): JsonResponse
    {
        $search = $request->query->get('q', '');
        
        $qb = $deckRepository->createQueryBuilder('d')
            ->where('d.user = :user')
            ->setParameter('user', $this->getUser());

        if (!empty($search)) {
            $qb->andWhere('LOWER(d.titre) LIKE :search 
                       OR LOWER(d.matiere) LIKE :search 
                       OR LOWER(d.niveau) LIKE :search')
               ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $decks = $qb->setMaxResults(10)
                    ->getQuery()
                    ->getResult();

        $results = [];
        foreach ($decks as $deck) {
            $results[] = [
                'id' => $deck->getIdDeck(),
                'titre' => $deck->getTitre(),
                'matiere' => $deck->getMatiere(),
                'niveau' => $deck->getNiveau(),
            ];
        }

        return new JsonResponse($results);
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
     * ✨ Afficher un deck avec ses flashcards
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

            // Traitement des fichiers 
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
