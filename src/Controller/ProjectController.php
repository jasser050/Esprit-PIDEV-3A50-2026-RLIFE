<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Form\ProjectFilterType;
use App\Repository\ProjectRepository;
use App\Repository\AssignmentRepository;
use App\Service\ProjectPdfService;
use App\Service\ProjectStatsService;
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

        // Liste des champs autorisés pour éviter les injections
        $allowedSortFields = ['titre', 'dateDebut', 'dateFin', 'statut', 'createdAt'];

        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'createdAt';
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        // Récupération des projets avec filtres
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

            $this->addFlash('success', 'Projet créé avec succès !');

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

            $this->addFlash('success', 'Projet modifié avec succès !');

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

            $this->addFlash('success', 'Projet supprimé avec succès !');
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
}