<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\AssignmentCollaborator;
use App\Entity\Project;
use App\Repository\AssignmentCollaboratorRepository;
use App\Repository\ProjectShareRepository;
use App\Repository\UserRepository;
use App\Service\NotificationManager;
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
    // ASSIGNATION DE TÂCHE
    // ============================================

    #[Route('/assignment/{id}/assign', name: 'app_collaboration_assign_task', methods: ['POST'])]
    public function assignTask(
        Assignment $assignment,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        AssignmentCollaboratorRepository $collaboratorRepository,
        ProjectShareRepository $shareRepository,
        NotificationManager $notificationManager
    ): Response {
        // Security check - only assignment owner can assign
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $email = $request->request->get('email');
        $userToAssign = $userRepository->findOneBy(['email' => $email]);

        if (!$userToAssign) {
            $this->addFlash('error', 'User not found with this email.');
            return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
        }

        if ($userToAssign === $this->getUser()) {
            $this->addFlash('error', 'You cannot assign to yourself.');
            return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
        }

        // Verify project is shared with this user
        $project = $assignment->getProject();
        if (!$shareRepository->hasAccess($project, $userToAssign)) {
            $this->addFlash('error', 'You must first share the project with this user.');
            return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
        }

        // Check if already assigned
        $existingCollab = $collaboratorRepository->findOneByAssignmentAndUser($assignment, $userToAssign);
        if ($existingCollab) {
            $this->addFlash('warning', 'This task is already assigned to this user.');
            return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
        }

        // Create the assignment
        $collaborator = new AssignmentCollaborator();
        $collaborator->setAssignment($assignment);
        $collaborator->setUser($userToAssign);
        $collaborator->setAssignedByUser($this->getUser());

        $entityManager->persist($collaborator);
        $entityManager->flush();

        $notificationManager->createNotification(
            $userToAssign,
            'Task assigned to you',
            sprintf('%s assigned you "%s".', $this->getUser()->getFullName() ?? $this->getUser()->getEmail(), $assignment->getTitre()),
            'task_assigned',
            $this->generateUrl('app_assignments_show', ['id' => $assignment->getId()])
        );

        $this->addFlash('success', 'Task assigned successfully!');
        return $this->redirectToRoute('app_assignments_show', ['id' => $assignment->getId()]);
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

        $this->addFlash('success', 'Collaborator removed successfully!');
        return $this->redirectToRoute('app_assignments_show', ['id' => $assignmentId]);
    }

    // ============================================
    // TÂCHES ASSIGNÉES À MOI
    // ============================================

    #[Route('/assigned-tasks', name: 'app_collaboration_assigned_tasks', methods: ['GET'])]
    public function assignedTasks(AssignmentCollaboratorRepository $collaboratorRepository): Response
    {
        $assignments = $collaboratorRepository->findByUser($this->getUser());

        return $this->render('pages/assignments/assigned_tasks.html.twig', [
            'assignments' => $assignments,
        ]);
    }
}
