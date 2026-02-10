<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Form\AssignmentType;
use App\Repository\AssignmentRepository;
use App\Service\AssignmentStatsService;
use App\Service\AssignmentPdfService;
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

        // Liste des champs autorisés pour le tri
        $allowedSortFields = ['titre', 'dateDebut', 'dateFin', 'priorite', 'statut', 'createdAt'];

        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'dateFin';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        // Récupération des assignments avec filtres
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

        // Si on vient d'un projet
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

    #[Route('/{id}', name: 'app_assignments_show', methods: ['GET'])]
    public function show(
        Assignment $assignment,
        AssignmentStatsService $statsService
    ): Response {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $assignmentStats = $statsService->getSingleAssignmentStats($assignment);

        return $this->render('pages/assignments/show.html.twig', [
            'assignment'      => $assignment,
            'assignmentStats' => $assignmentStats,
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
}