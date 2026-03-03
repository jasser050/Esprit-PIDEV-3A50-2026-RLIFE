<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\Comment;
use App\Entity\Project;
use App\Form\AssignmentType;
use App\Form\CommentType;
use App\Repository\AssignmentRepository;
use App\Repository\AssignmentCollaboratorRepository;
use App\Repository\CommentRepository;
use App\Repository\ProjectShareRepository;
use App\Service\AssignmentStatsService;
use App\Service\AssignmentPdfService;
use App\Service\NotificationManager;
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
        $aiPlan = $request->getSession()->get('ai_productivity_plan', []);
        $aiChallenges = $request->getSession()->get('ai_productivity_challenges', []);
        if (!is_array($aiChallenges)) {
            $aiChallenges = [];
        }

        return $this->render('pages/assignments/index.html.twig', [
            'assignments' => $assignments,
            'sort'        => $sort,
            'direction'   => strtolower($direction),
            'priorite'    => $priorite,
            'statut'      => $statut,
            'search'      => $search,
            'stats'       => $stats,
            'ai_plan'     => is_array($aiPlan) ? $aiPlan : [],
            'ai_challenges' => $aiChallenges,
        ]);
    }

    #[Route('/new', name: 'app_assignments_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ProjectShareRepository $shareRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $assignment = new Assignment();
        $currentUser = $this->getUser();

        // If coming from a project
        if ($projectId = $request->query->get('project_id')) {
            $project = $entityManager->getRepository(Project::class)->find($projectId);
            $canUseProject = false;
            if ($project && $project->getUser() === $currentUser) {
                $canUseProject = true;
            } elseif ($project && $currentUser) {
                $share = $shareRepository->findOneByProjectAndUser($project, $currentUser);
                $canUseProject = $share !== null && $share->getRole() === 'editor';
            }

            if ($canUseProject) {
                $assignment->setProject($project);
            } else {
                $this->addFlash('error', 'You do not have permission to add tasks to this project.');
            }
        }

        $form = $this->createForm(AssignmentType::class, $assignment, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (trim((string) $assignment->getTitre()) === '') {
                $this->addFlash('error', 'Task title is required.');
                return $this->render('pages/assignments/new.html.twig', [
                    'assignment' => $assignment,
                    'form' => $form,
                ]);
            }

            if ($assignment->getDateFin() === null) {
                $assignment->setDateFin($assignment->getDateDebut() ?? new \DateTimeImmutable('today'));
            }

            if ($assignment->getDateDebut() === null) {
                $assignment->setDateDebut($assignment->getDateFin() ?? new \DateTimeImmutable('today'));
            }

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
        int $id,
        Request $request,
        AssignmentRepository $assignmentRepository,
        AssignmentStatsService $statsService,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager,
        AssignmentCollaboratorRepository $collaboratorRepository,
        ProjectShareRepository $shareRepository,
        NotificationManager $notificationManager
    ): Response {
        $assignment = $assignmentRepository->find($id);
        if (!$assignment instanceof Assignment) {
            throw $this->createNotFoundException('Assignment not found.');
        }

        // Check if user is owner, collaborator, or has project access
        $isOwner = $assignment->getUser() === $this->getUser();
        $isCollaborator = $collaboratorRepository->findOneByAssignmentAndUser($assignment, $this->getUser()) !== null;
        $projectShare = $shareRepository->findOneByProjectAndUser($assignment->getProject(), $this->getUser());
        $hasProjectAccess = $projectShare !== null;
        $isViewerOnProject = $projectShare !== null && $projectShare->getRole() === 'viewer';

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

            $assignmentOwner = $assignment->getUser();
            $currentUser = $this->getUser();
            if ($assignmentOwner && $currentUser && $assignmentOwner->getId() !== $currentUser->getId()) {
                $notificationManager->createNotification(
                    $assignmentOwner,
                    'New comment on assignment',
                    sprintf('%s commented on "%s".', $currentUser->getFullName(), $assignment->getTitre()),
                    'new_comment',
                    $this->generateUrl('app_assignments_show', ['id' => $assignment->getId()])
                );
            }

            $this->addFlash('success', 'Comment added successfully!');
            return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
        }

        // Get comments for this assignment
        $comments = $commentRepository->findByAssignment($assignment);
        $collaborators = $collaboratorRepository->findByAssignment($assignment);

        return $this->render('pages/assignments/show.html.twig', [
            'assignment'      => $assignment,
            'assignmentStats' => $assignmentStats,
            'collaborators'   => $collaborators,
            'comments'        => $comments,
            'commentForm'     => $commentForm->createView(),
            'can_edit_assignment' => $isOwner && !$isViewerOnProject,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_assignments_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        int $id,
        AssignmentRepository $assignmentRepository,
        ProjectShareRepository $shareRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $assignment = $assignmentRepository->find($id);
        if (!$assignment instanceof Assignment) {
            throw $this->createNotFoundException('Assignment not found.');
        }

        $currentUser = $this->getUser();
        $isOwner = $assignment->getUser() === $currentUser;

        $project = $assignment->getProject();
        $share = $project && $currentUser ? $shareRepository->findOneByProjectAndUser($project, $currentUser) : null;
        $isViewerOnProject = $share !== null && $share->getRole() === 'viewer';

        if (!$isOwner || $isViewerOnProject) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AssignmentType::class, $assignment, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (trim((string) $assignment->getTitre()) === '') {
                $this->addFlash('error', 'Task title is required.');
                return $this->render('pages/assignments/edit.html.twig', [
                    'assignment' => $assignment,
                    'form' => $form,
                ]);
            }

            if ($assignment->getDateFin() === null) {
                $assignment->setDateFin($assignment->getDateDebut() ?? new \DateTimeImmutable('today'));
            }

            if ($assignment->getDateDebut() === null) {
                $assignment->setDateDebut($assignment->getDateFin() ?? new \DateTimeImmutable('today'));
            }

            $entityManager->flush();

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
        int $id,
        AssignmentRepository $assignmentRepository,
        ProjectShareRepository $shareRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $assignment = $assignmentRepository->find($id);
        if (!$assignment instanceof Assignment) {
            throw $this->createNotFoundException('Assignment not found.');
        }

        $currentUser = $this->getUser();
        $isOwner = $assignment->getUser() === $currentUser;

        $project = $assignment->getProject();
        $share = $project && $currentUser ? $shareRepository->findOneByProjectAndUser($project, $currentUser) : null;
        $isViewerOnProject = $share !== null && $share->getRole() === 'viewer';

        if (!$isOwner || $isViewerOnProject) {
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
        int $id,
        AssignmentRepository $assignmentRepository,
        AssignmentPdfService $pdfService
    ): Response {
        $assignment = $assignmentRepository->find($id);
        if (!$assignment instanceof Assignment) {
            throw $this->createNotFoundException('Assignment not found.');
        }

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
        if ($this->isCsrfTokenValid('delete-comment' . $comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment deleted successfully!');
        }

        return $this->redirectToRoute('app_assignments_show', ['id' => $assignmentId]);
    }
}
