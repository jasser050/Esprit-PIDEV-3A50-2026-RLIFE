<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectShare;
use App\Entity\User;
use App\Form\ProjectType;
use App\Form\ProjectFilterType;
use App\Repository\ProjectRepository;
use App\Repository\ProjectShareRepository;
use App\Repository\AssignmentRepository;
use App\Repository\UserRepository;
use App\Service\ProjectPdfService;
use App\Service\ProjectStatsService;
use App\Service\PusherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/project')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_project_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProjectRepository $projectRepository,
        ProjectStatsService $statsService
    ): Response {
        $sort      = $request->query->getString('sort', 'createdAt');
        $direction = $request->query->getString('direction', 'DESC');
        $statut    = $request->query->getString('statut', '');
        $search    = $request->query->getString('search', '');

        // List of allowed fields to prevent injections
        $allowedSortFields = ['titre', 'dateDebut', 'dateFin', 'statut', 'createdAt'];

        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'createdAt';
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        // Retrieve projects with filters
        $projects = $projectRepository->findByUserWithFilters(
            user: $this->getUser(),
            sort: $sort,
            direction: $direction,
            statut: $statut,
            search: $search
        );

        // Statistiques
        $stats = $statsService->getProjectStats($this->getUser());

        return $this->render('pages/projects/index.html.twig', [
            'projects'  => $projects,
            'sort'      => $sort,
            'direction' => strtolower($direction),
            'statut'    => $statut,
            'search'    => $search,
            'stats'     => $stats,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setUser($this->getUser());

            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Project created successfully!');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('pages/projects/new.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(
        Project $project,
        AssignmentRepository $assignmentRepository,
        ProjectStatsService $statsService
    ): Response {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $assignments = $assignmentRepository->findByProject($project);
        $projectStats = $statsService->getSingleProjectStats($project);

        return $this->render('pages/projects/show.html.twig', [
            'project'      => $project,
            'assignments'  => $assignments,
            'projectStats' => $projectStats,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager
    ): Response {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Project updated successfully!');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('pages/projects/edit.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_project_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager
    ): Response {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', 'Project deleted successfully!');
        }

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/export/pdf', name: 'app_project_export_pdf', methods: ['GET'])]
    public function exportPdf(
        Request $request,
        ProjectRepository $projectRepository,
        ProjectPdfService $pdfService
    ): Response {
        $statut = $request->query->getString('statut', '');
        $search = $request->query->getString('search', '');

        $projects = $projectRepository->findByUserWithFilters(
            user: $this->getUser(),
            sort: 'createdAt',
            direction: 'DESC',
            statut: $statut,
            search: $search
        );

        return $pdfService->generateProjectListPdf($projects, $this->getUser());
    }

    #[Route('/{id}/export/pdf', name: 'app_project_export_single_pdf', methods: ['GET'])]
    public function exportSinglePdf(
        Project $project,
        AssignmentRepository $assignmentRepository,
        ProjectPdfService $pdfService
    ): Response {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $assignments = $assignmentRepository->findByProject($project);

        return $pdfService->generateSingleProjectPdf($project, $assignments);
    }

    #[Route('/stats/data', name: 'app_project_stats_data', methods: ['GET'])]
    public function statsData(
        ProjectStatsService $statsService
    ): Response {
        $stats = $statsService->getProjectStats($this->getUser());
        
        return $this->json([
            'total' => $stats['total'],
            'enCours' => $stats['enCours'],
            'termines' => $stats['termines'],
            'enAttente' => $stats['enAttente'],
            'enRetard' => $stats['enRetard'],
            'chartData' => $stats['chartData'],
        ]);
    }

    #[Route('/{id}/share', name: 'app_project_share', methods: ['POST'])]
    public function share(
        Project $project,
        Request $request,
        UserRepository $userRepository,
        ProjectShareRepository $shareRepository,
        EntityManagerInterface $entityManager,
        PusherService $pusherService
    ): Response {
        // Security check - only project owner can share
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $email = $request->request->get('email');
        $role = $request->request->get('role', 'viewer');

        $userToShare = $userRepository->findOneBy(['email' => $email]);

        if (!$userToShare) {
            $this->addFlash('error', 'User not found with this email.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        if ($userToShare === $this->getUser()) {
            $this->addFlash('error', 'You cannot share with yourself.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        // Check if already shared
        $existingShare = $shareRepository->findOneByProjectAndUser($project, $userToShare);
        if ($existingShare) {
            $this->addFlash('warning', 'This project is already shared with this user.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        // Create the share
        $projectShare = new ProjectShare();
        $projectShare->setProject($project);
        $projectShare->setSharedWithUser($userToShare);
        $projectShare->setSharedByUser($this->getUser());
        $projectShare->setRole($role);

        $entityManager->persist($projectShare);
        $entityManager->flush();

        // Send real-time notification via Pusher
        $pusherService->notifyUserProjectShared(
            $userToShare->getId(),
            $project->getId(),
            $project->getTitre(),
            $this->getUser()->getFullName() ?? $this->getUser()->getEmail(),
            $role
        );

        $this->addFlash('success', 'Project shared successfully!');
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{projectId}/share/{shareId}/remove', name: 'app_project_remove_share', methods: ['POST'])]
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

        $this->addFlash('success', 'Share removed successfully!');
        return $this->redirectToRoute('app_project_show', ['id' => $projectId]);
    }

    #[Route('/shared-with-me', name: 'app_project_shared_with_me', methods: ['GET'])]
    public function sharedWithMe(
        ProjectShareRepository $shareRepository
    ): Response {
        $shares = $shareRepository->findBySharedWithUser($this->getUser());

        return $this->render('pages/projects/shared_with_me.html.twig', [
            'shares' => $shares,
        ]);
    }
}
