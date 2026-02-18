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
use App\Service\NotificationManager;
use App\Service\RewardService; // ← AJOUT pour le système de récompenses
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\AssignmentCollaboratorRepository;
use App\Repository\ProjectShareRepository;  

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

        // List of allowed fields for sorting
        $allowedSortFields = ['titre', 'dateDebut', 'dateFin', 'priorite', 'statut', 'createdAt'];

        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'dateFin';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        // Retrieve assignments with filters
        $assignments = $assignmentRepository->findByUserWithFilters(
            user: $this->getUser(),
            sort: $sort,
            direction: $direction,
            priorite: $priorite,
            statut: $statut,
            search: $search
        );

        // Statistiques
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

        // If coming from a project
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

            $this->addFlash('success', 'Task created successfully!');

            return $this->redirectToRoute('app_assignments');
        }

        return $this->render('pages/assignments/new.html.twig', [
            'assignment' => $assignment,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_assignments_show', methods: ['GET', 'POST'])]
    public function show(
        Assignment $assignment,
        Request $request,
        AssignmentStatsService $statsService,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager,
        NotificationManager $notificationManager,
        AssignmentCollaboratorRepository $collaboratorRepository,
        ProjectShareRepository $shareRepository
    ): Response {
        // Check if user is owner, collaborator, or has project access
        $isOwner = $assignment->getUser() === $this->getUser();
        $isCollaborator = $collaboratorRepository->findOneByAssignmentAndUser($assignment, $this->getUser()) !== null;
        $hasProjectAccess = $shareRepository->hasAccess($assignment->getProject(), $this->getUser());

        if (!$isOwner && !$isCollaborator && !$hasProjectAccess) {
            throw $this->createAccessDeniedException();
        }

        $assignmentStats = $statsService->getSingleAssignmentStats($assignment);

        // Handle comment submission
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setAssignment($assignment);
            $comment->setUser($this->getUser());

            $entityManager->persist($comment);
            $entityManager->flush();

            $taskOwner = $comment->getAssignment()->getUser();
            if ($taskOwner !== $this->getUser()) {
                $notificationManager->createNotification(
                    $taskOwner,
                    'Nouveau commentaire',
                    "{$this->getUser()->getFullName()} a commente la tache \"{$comment->getAssignment()->getTitre()}\"",
                    'new_comment',
                    $this->generateUrl('app_assignments_show', ['id' => $comment->getAssignment()->getId()])
                );
            }

            $this->addFlash('success', 'Comment added successfully!');
            return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
        }

        // Get comments for this assignment
        $comments = $commentRepository->findByAssignment($assignment);

        return $this->render('pages/assignments/show.html.twig', [
            'assignment'      => $assignment,
            'assignmentStats' => $assignmentStats,
            'comments'        => $comments,
            'commentForm'     => $commentForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_assignments_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Assignment $assignment,
        EntityManagerInterface $entityManager,
        RewardService $rewardService // ← AJOUT : injection du service de récompenses
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

            // AJOUT : Récompense coins si la tâche est terminée avant l'échéance
            $rewardService->awardCoinsForAssignment($this->getUser(), $assignment);

            $this->addFlash('success', 'Task updated successfully!');

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

            $this->addFlash('success', 'Task deleted successfully!');
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

    #[Route('/{id}/export/pdf', name: 'app_assignments_export_single_pdf', methods: ['GET'])]
    public function exportSinglePdf(
        Assignment $assignment,
        AssignmentPdfService $pdfService
    ): Response {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $pdfService->generateSingleAssignmentPdf($assignment);
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

    #[Route('/comment/{id}/delete', name: 'app_assignments_delete_comment', methods: ['POST'])]
    public function deleteComment(
        Comment $comment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Security check - only comment author can delete
        if ($comment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $assignmentId = $comment->getAssignment()->getId();
        $commentId = $comment->getId();

        if ($this->isCsrfTokenValid('delete-comment' . $comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment deleted successfully!');
        }

        return $this->redirectToRoute('app_assignments_show', ['id' => $assignmentId]);
    }
}

