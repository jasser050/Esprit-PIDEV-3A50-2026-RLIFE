<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\ProjectShare;
use App\Entity\AssignmentCollaborator;
use App\Entity\Project;
use App\Entity\Assignment;
use App\Repository\CommentRepository;
use App\Repository\ProjectShareRepository;
use App\Repository\AssignmentCollaboratorRepository;
use App\Repository\UserRepository;
use App\Service\PusherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/collaboration')]
#[IsGranted('ROLE_USER')]
class CollaborationController extends AbstractController
{
    // ============================================
    // PARTAGE DE PROJET (Feature 6.1)
    // ============================================

    #[Route('/project/{id}/share', name: 'app_collaboration_share_project', methods: ['GET', 'POST'])]
    public function shareProject(
        Project $project,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ProjectShareRepository $shareRepository,
        PusherService $pusherService
    ): Response {
        // Vérifier que l'utilisateur est propriétaire du projet
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $role = $request->request->get('role', 'viewer');

            $userToShare = $userRepository->findOneBy(['email' => $email]);

            if (!$userToShare) {
                $this->addFlash('error', 'Utilisateur non trouvé avec cet email.');
                return $this->redirectToRoute('app_collaboration_share_project', ['id' => $project->getId()]);
            }

            if ($userToShare === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas partager avec vous-même.');
                return $this->redirectToRoute('app_collaboration_share_project', ['id' => $project->getId()]);
            }

            // Vérifier si déjà partagé
            $existingShare = $shareRepository->findOneByProjectAndUser($project, $userToShare);
            if ($existingShare) {
                $this->addFlash('warning', 'Ce projet est déjà partagé avec cet utilisateur.');
                return $this->redirectToRoute('app_collaboration_share_project', ['id' => $project->getId()]);
            }

            // Créer le partage
            $projectShare = new ProjectShare();
            $projectShare->setProject($project);
            $projectShare->setSharedWithUser($userToShare);
            $projectShare->setSharedByUser($this->getUser());
            $projectShare->setRole($role);

            $entityManager->persist($projectShare);
            $entityManager->flush();
            

           // Après avoir persisté le partage
$pusherService->notifyUserProjectShared(
    $userToShare->getId(),
    $project->getId(),
    $project->getTitre(),
    $this->getUser()->getFullName() ?? $this->getUser()->getEmail(),
    $role
);

            $this->addFlash('success', 'Projet partagé avec succès!');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        // Récupérer les partages existants
        $shares = $shareRepository->findByProject($project);

       return $this->render('collaboration/share_project.html.twig', [
    'project' => $project,
    'shares'  => $shareRepository->findByProject($project), // Liste des partages actuels
]);
    }

    #[Route('/project/{projectId}/share/{shareId}/remove', name: 'app_collaboration_remove_share', methods: ['POST'])]
    public function removeShare(
        int $projectId,
        int $shareId,
        ProjectShareRepository $shareRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $share = $shareRepository->find($shareId);

        if (!$share || $share->getProject()->getId() !== $projectId) {
            throw $this->createNotFoundException();
        }

        if ($share->getProject()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($share);
        $entityManager->flush();

        $this->addFlash('success', 'Partage supprimé avec succès!');
        return $this->redirectToRoute('app_project_show', ['id' => $projectId]);
    }

    // ============================================
    // ASSIGNATION DE TÂCHE (Feature 6.2)
    // ============================================

    #[Route('/assignment/{id}/assign', name: 'app_collaboration_assign_task', methods: ['GET', 'POST'])]
    public function assignTask(
        Assignment $assignment,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        AssignmentCollaboratorRepository $collaboratorRepository,
        ProjectShareRepository $shareRepository,
        PusherService $pusherService
    ): Response {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $userToAssign = $userRepository->findOneBy(['email' => $email]);

            if (!$userToAssign) {
                $this->addFlash('error', 'Utilisateur non trouvé avec cet email.');
                return $this->redirectToRoute('app_collaboration_assign_task', ['id' => $assignment->getId()]);
            }

            if ($userToAssign === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas vous assigner vous-même.');
                return $this->redirectToRoute('app_collaboration_assign_task', ['id' => $assignment->getId()]);
            }

            // Vérifier si le projet est partagé
            $project = $assignment->getProject();
            if (!$shareRepository->hasAccess($project, $userToAssign)) {
                $this->addFlash('error', 'Vous devez d\'abord partager le projet avec cet utilisateur.');
                return $this->redirectToRoute('app_collaboration_assign_task', ['id' => $assignment->getId()]);
            }

            // Vérifier si déjà assigné
            $existingCollab = $collaboratorRepository->findOneByAssignmentAndUser($assignment, $userToAssign);
            if ($existingCollab) {
                $this->addFlash('warning', 'Cette tâche est déjà assignée à cet utilisateur.');
                return $this->redirectToRoute('app_collaboration_assign_task', ['id' => $assignment->getId()]);
            }

            // Créer l'assignation
            $collaborator = new AssignmentCollaborator();
            $collaborator->setAssignment($assignment);
            $collaborator->setUser($userToAssign);
            $collaborator->setAssignedByUser($this->getUser());

            $entityManager->persist($collaborator);
            $entityManager->flush();

            $pusherService->notifyUserTaskAssigned(
    $assignedUser->getId(),
    $assignment->getId(),
    $assignment->getTitre(),
    $this->getUser()->getFullName() ?? $this->getUser()->getEmail()
);

            $this->addFlash('success', 'Tâche assignée avec succès!');
            return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
        }

        $collaborators = $collaboratorRepository->findByAssignment($assignment);

        return $this->render('collaboration/assign_task.html.twig', [
            'assignment' => $assignment,
            'collaborators' => $collaborators,
        ]);
    }

    #[Route('/assignment/{assignmentId}/collaborator/{collaboratorId}/remove', name: 'app_collaboration_remove_collaborator', methods: ['POST'])]
    public function removeCollaborator(
        int $assignmentId,
        int $collaboratorId,
        AssignmentCollaboratorRepository $collaboratorRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $collaborator = $collaboratorRepository->find($collaboratorId);

        if (!$collaborator || $collaborator->getAssignment()->getId() !== $assignmentId) {
            throw $this->createNotFoundException();
        }

        if ($collaborator->getAssignment()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($collaborator);
        $entityManager->flush();

        $this->addFlash('success', 'Collaborateur retiré avec succès!');
        return $this->redirectToRoute('app_assignments_show', ['id' => $assignmentId]);
    }

    // ============================================
    // COMMENTAIRES SUR TÂCHE (Feature 6.3) - TEMPS RÉEL
    // ============================================

    #[Route('/assignment/{id}/comments', name: 'app_collaboration_comments', methods: ['GET', 'POST'])]
    public function comments(
        Assignment $assignment,
        Request $request,
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
        ProjectShareRepository $shareRepository,
        PusherService $pusherService
    ): Response {
        // Vérifier l'accès
        $project = $assignment->getProject();
        if ($assignment->getUser() !== $this->getUser() && 
            !$shareRepository->hasAccess($project, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');

            if (empty($content)) {
                $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
                return $this->redirectToRoute('app_collaboration_comments', ['id' => $assignment->getId()]);
            }

            // Créer le commentaire
            $comment = new Comment();
            $comment->setAssignment($assignment);
            $comment->setUser($this->getUser());
            $comment->setContent($content);

            $entityManager->persist($comment);
            $entityManager->flush();

            // Notification Pusher TEMPS RÉEL
            $pusherService->notifyNewComment(
                $assignment->getId(),
                $comment->getId(),
                $this->getUser()->getEmail(),
                $comment->getContent(),
                $comment->getCreatedAt()->format('Y-m-d H:i:s')
            );

            $this->addFlash('success', 'Commentaire ajouté avec succès!');
            return $this->redirectToRoute('app_collaboration_comments', ['id' => $assignment->getId()]);
        }

        $comments = $commentRepository->findByAssignment($assignment);

        return $this->render('collaboration/comments.html.twig', [
            'assignment' => $assignment,
            'comments' => $comments,
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'app_collaboration_delete_comment', methods: ['POST'])]
    public function deleteComment(
        Comment $comment,
        EntityManagerInterface $entityManager,
        PusherService $pusherService
    ): Response {
        if ($comment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $assignmentId = $comment->getAssignment()->getId();
        $commentId = $comment->getId();

        $entityManager->remove($comment);
        $entityManager->flush();

        // Notification Pusher
        $pusherService->notifyCommentDeleted($assignmentId, $commentId);

        $this->addFlash('success', 'Commentaire supprimé avec succès!');
        return $this->redirectToRoute('app_collaboration_comments', ['id' => $assignmentId]);
    }

    // ============================================
    // PROJETS PARTAGÉS AVEC MOI
    // ============================================

    #[Route('/shared-projects', name: 'app_collaboration_shared_projects', methods: ['GET'])]
    public function sharedProjects(ProjectShareRepository $shareRepository): Response
    {
        $shares = $shareRepository->findBySharedWithUser($this->getUser());

        return $this->render('collaboration/shared_projects.html.twig', [
            'shares' => $shares,
        ]);
    }

    // ============================================
    // TÂCHES ASSIGNÉES À MOI
    // ============================================

    #[Route('/assigned-tasks', name: 'app_collaboration_assigned_tasks', methods: ['GET'])]
    public function assignedTasks(AssignmentCollaboratorRepository $collaboratorRepository): Response
    {
        $assignments = $collaboratorRepository->findByUser($this->getUser());

        return $this->render('collaboration/assigned_tasks.html.twig', [
            'assignments' => $assignments,
        ]);
    }
}