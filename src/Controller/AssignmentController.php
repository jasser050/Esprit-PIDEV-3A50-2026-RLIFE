<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\Comment;
use App\Entity\Project;
use App\Form\AssignmentType;
use App\Form\CommentType;
use App\Repository\AssignmentRepository;
use App\Repository\CommentRepository;
use App\Service\AssignmentStatsService;
use App\Service\AssignmentPdfService;
use App\Service\PusherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/assignments')]
#[IsGranted('ROLE_USER')]
class AssignmentController extends AbstractController
{
    #[Route('/', name: 'app_assignments', methods: ['GET'])]
    public function index(
        Request $request,
        AssignmentRepository $assignmentRepository,
        AssignmentStatsService $statsService
    ): Response {
        $sort      = $request->query->getString('sort', 'dateFin');
        $direction = $request->query->getString('direction', 'ASC');
        $priorite  = $request->query->getString('priorite', '');
        $statut    = $request->query->getString('statut', '');
        $search    = $request->query->getString('search', '');

        $allowedSortFields = ['titre', 'dateDebut', 'dateFin', 'priorite', 'statut', 'createdAt'];

        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'dateFin';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $assignments = $assignmentRepository->findByUserWithFilters(
            user: $this->getUser(),
            sort: $sort,
            direction: $direction,
            priorite: $priorite,
            statut: $statut,
            search: $search
        );

        $stats = $statsService->getAssignmentStats($this->getUser());

        return $this->render('pages/assignments/index.html.twig', [
            'assignments' => $assignments,
            'sort'        => $sort,
            'direction'   => strtolower($direction),
            'priorite'    => $priorite,
            'statut'      => $statut,
            'search'      => $search,
            'stats'       => $stats,
        ]);
    }

    #[Route('/new', name: 'app_assignments_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $assignment = new Assignment();

        if ($projectId = $request->query->get('project_id')) {
            $project = $entityManager->getRepository(Project::class)->find($projectId);
            if ($project && $project->getUser() === $this->getUser()) {
                $assignment->setProject($project);
            }
        }

        $form = $this->createForm(AssignmentType::class, $assignment, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $assignment->setUser($this->getUser());

            $entityManager->persist($assignment);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche créée avec succès !');

            return $this->redirectToRoute('app_assignments');
        }

        return $this->render('pages/assignments/new.html.twig', [
            'assignment' => $assignment,
            'form'       => $form,
        ]);
    }

   #[Route('/assignments/{id}', name: 'app_assignments_show', methods: ['GET', 'POST'])]
public function show(
    Assignment $assignment,
    Request $request,
    CommentRepository $commentRepository,
    EntityManagerInterface $entityManager,
    PusherService $pusherService
): Response {
    // Sécurité : vérifier que l'utilisateur a accès à cette tâche (propriétaire ou collaborateur ou projet partagé)
    // (tu as déjà une logique similaire ailleurs, réutilise-la)

    // Charger les commentaires triés par date décroissante
    $comments = $commentRepository->findByAssignment($assignment);

    // Formulaire pour ajouter un commentaire
    $comment = new Comment();
    $comment->setAssignment($assignment);
    $comment->setUser($this->getUser());

    $commentForm = $this->createForm(CommentType::class, $comment);
    $commentForm->handleRequest($request);

    if ($commentForm->isSubmitted() && $commentForm->isValid()) {
        $comment->setCreatedAt(new \DateTime());
        $entityManager->persist($comment);
        $entityManager->flush();

        // Notification Pusher en temps réel
        $pusherService->notifyNewComment(
            $assignment->getId(),
            $comment->getId(),
            $comment->getUser()->getEmail(),
            $comment->getContent(),
            $comment->getCreatedAt()->format('d/m/Y H:i')
        );

        $this->addFlash('success', 'Commentaire ajouté !');
        return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
    }

    return $this->render('pages/assignments/show.html.twig', [
        'assignment' => $assignment,
        'comments' => $comments,
        'commentForm' => $commentForm->createView(),
    ]);
}

    #[Route('/{id}/edit', name: 'app_assignments_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Assignment $assignment,
        EntityManagerInterface $entityManager
    ): Response {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AssignmentType::class, $assignment, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Tâche modifiée avec succès !');

            return $this->redirectToRoute('app_assignments');
        }

        return $this->render('pages/assignments/edit.html.twig', [
            'assignment' => $assignment,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_assignments_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Assignment $assignment,
        EntityManagerInterface $entityManager
    ): Response {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $assignment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($assignment);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche supprimée avec succès !');
        }

        return $this->redirectToRoute('app_assignments');
    }

    #[Route('/export/pdf', name: 'app_assignments_export_pdf', methods: ['GET'])]
    public function exportPdf(
        Request $request,
        AssignmentRepository $assignmentRepository,
        AssignmentPdfService $pdfService
    ): Response {
        $priorite = $request->query->getString('priorite', '');
        $statut   = $request->query->getString('statut', '');
        $search   = $request->query->getString('search', '');

        $assignments = $assignmentRepository->findByUserWithFilters(
            user: $this->getUser(),
            sort: 'dateFin',
            direction: 'ASC',
            priorite: $priorite,
            statut: $statut,
            search: $search
        );

        return $pdfService->generateAssignmentListPdf($assignments, $this->getUser());
    }

    /**
     * Export d'une seule tâche en PDF avec ses statistiques
     */
    #[Route('/{id}/export/pdf', name: 'app_assignments_export_single_pdf', methods: ['GET'])]
    public function exportSinglePdf(
        Assignment $assignment,
        AssignmentStatsService $statsService,           // ← Ajouté ici
        AssignmentPdfService $pdfService
    ): Response {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Calcul des statistiques (exactement comme dans show())
        $assignmentStats = $statsService->getSingleAssignmentStats($assignment);

        // Passage des DEUX paramètres au service PDF
        return $pdfService->generateSingleAssignmentPdf($assignment, $assignmentStats);
    }

    #[Route('/stats/data', name: 'app_assignments_stats_data', methods: ['GET'])]
    public function statsData(
        AssignmentStatsService $statsService
    ): Response {
        $stats = $statsService->getAssignmentStats($this->getUser());

        return $this->json([
            'total'      => $stats['total'],
            'aFaire'     => $stats['aFaire'],
            'enCours'    => $stats['enCours'],
            'termines'   => $stats['termines'],
            'enRetard'   => $stats['enRetard'],
            'chartData'  => $stats['chartData'],
        ]);
    }
}