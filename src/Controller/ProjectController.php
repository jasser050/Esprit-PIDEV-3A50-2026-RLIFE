<?php

namespace App\Controller;

use App\Entity\Assignment;
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
use App\Service\NotificationManager;
use App\Service\ProductivityAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        $aiPlan = $request->getSession()->get('ai_productivity_plan', []);
        $aiChallenges = $request->getSession()->get('ai_productivity_challenges', []);
        if (!is_array($aiChallenges)) {
            $aiChallenges = [];
        }

        return $this->render('pages/projects/index.html.twig', [
            'projects'  => $projects,
            'sort'      => $sort,
            'direction' => strtolower($direction),
            'statut'    => $statut,
            'search'    => $search,
            'stats'     => $stats,
            'ai_plan'   => is_array($aiPlan) ? $aiPlan : [],
            'ai_challenges' => $aiChallenges,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductivityAiService $productivityAiService
    ): Response {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (trim((string) $project->getTitre()) === '') {
                $this->addFlash('error', 'Project title is required.');
                return $this->render('pages/projects/new.html.twig', [
                    'project' => $project,
                    'form' => $form,
                ]);
            }

            if ($project->getDateFin() === null) {
                $project->setDateFin($project->getDateDebut() ?? new \DateTimeImmutable('today'));
            }
            if ($project->getDateDebut() === null) {
                $project->setDateDebut($project->getDateFin() ?? new \DateTimeImmutable('today'));
            }

            $project->setUser($this->getUser());

            $entityManager->persist($project);
            $entityManager->flush();

            $suggestions = $productivityAiService->generateAssignmentsForProject($this->getUser(), $project);
            $request->getSession()->set(sprintf('project_ai_suggestions_%d', $project->getId()), $suggestions);

            $this->addFlash('success', sprintf('Project created successfully! AI prepared %d task suggestions.', count($suggestions)));

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('pages/projects/new.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(
        Request $request,
        Project $project,
        AssignmentRepository $assignmentRepository,
        ProjectShareRepository $shareRepository,
        ProjectStatsService $statsService
    ): Response {
        $isOwner = $project->getUser() === $this->getUser();
        $hasAccess = $shareRepository->hasAccess($project, $this->getUser());

        if (!$isOwner && !$hasAccess) {
            throw $this->createAccessDeniedException();
        }

        $assignments = $assignmentRepository->findByProject($project);
        $shares = $shareRepository->findByProject($project);
        $projectStats = $statsService->getSingleProjectStats($project);
        $aiSuggestions = $request->getSession()->get(sprintf('project_ai_suggestions_%d', $project->getId()), []);
        if (!is_array($aiSuggestions)) {
            $aiSuggestions = [];
        }
        $aiRisk = $request->getSession()->get(sprintf('project_ai_risk_%d', $project->getId()), []);
        $aiBalance = $request->getSession()->get(sprintf('project_ai_balance_%d', $project->getId()), []);
        $aiQuality = $request->getSession()->get(sprintf('project_ai_quality_%d', $project->getId()), []);
        $aiSprint = $request->getSession()->get(sprintf('project_ai_sprint_%d', $project->getId()), []);
        $aiReport = $request->getSession()->get(sprintf('project_ai_report_%d', $project->getId()), []);

        return $this->render('pages/projects/show.html.twig', [
            'project'      => $project,
            'assignments'  => $assignments,
            'shares'       => $shares,
            'projectStats' => $projectStats,
            'ai_suggestions' => $aiSuggestions,
            'ai_risk' => is_array($aiRisk) ? $aiRisk : [],
            'ai_balance' => is_array($aiBalance) ? $aiBalance : [],
            'ai_quality' => is_array($aiQuality) ? $aiQuality : [],
            'ai_sprint' => is_array($aiSprint) ? $aiSprint : [],
            'ai_report' => is_array($aiReport) ? $aiReport : [],
        ]);
    }

    #[Route('/{id}/ai-report/generate', name: 'app_project_ai_report_generate', methods: ['POST'])]
    public function generateAiReport(
        Request $request,
        Project $project,
        AssignmentRepository $assignmentRepository,
        ProductivityAiService $productivityAiService
    ): RedirectResponse {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('generate_project_ai_report_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid AI report request.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        if (!$this->isDoneStatus((string) $project->getStatut())) {
            $this->addFlash('warning', 'AI final report is available only when the project is completed.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $assignments = $assignmentRepository->findByProject($project);
        $report = $productivityAiService->generateProjectCompletionReport($this->getUser(), $project, $assignments);
        $request->getSession()->set(sprintf('project_ai_report_%d', $project->getId()), $report);

        $this->addFlash('success', 'AI completion report generated. You can now download the PDF.');
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/ai-report/pdf', name: 'app_project_ai_report_pdf', methods: ['GET'])]
    public function downloadAiReportPdf(
        Request $request,
        Project $project,
        AssignmentRepository $assignmentRepository,
        ProjectPdfService $projectPdfService
    ): Response {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isDoneStatus((string) $project->getStatut())) {
            $this->addFlash('warning', 'Only completed projects can export AI final report.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $report = $request->getSession()->get(sprintf('project_ai_report_%d', $project->getId()), []);
        if (!is_array($report) || $report === []) {
            $this->addFlash('warning', 'Generate the AI report before downloading PDF.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $assignments = $assignmentRepository->findByProject($project);
        return $projectPdfService->generateAiCompletionReportPdf($project, $assignments, $report);
    }

    #[Route('/{id}/ai-suggestions/generate', name: 'app_project_ai_suggestions_generate', methods: ['POST'])]
    public function generateAiSuggestions(
        Request $request,
        Project $project,
        ProductivityAiService $productivityAiService
    ): RedirectResponse {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('generate_project_ai_suggestions_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid AI request.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $suggestions = $productivityAiService->generateAssignmentsForProject($this->getUser(), $project);
        $request->getSession()->set(sprintf('project_ai_suggestions_%d', $project->getId()), $suggestions);

        $this->addFlash('success', sprintf('AI regenerated %d suggestions for this project.', count($suggestions)));
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/ai-toolkit/generate', name: 'app_project_ai_toolkit_generate', methods: ['POST'])]
    public function generateAiToolkit(
        Request $request,
        Project $project,
        AssignmentRepository $assignmentRepository,
        ProductivityAiService $productivityAiService
    ): RedirectResponse {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('generate_project_ai_toolkit_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid AI toolkit request.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $assignments = $assignmentRepository->findByProject($project);
        $request->getSession()->set(
            sprintf('project_ai_risk_%d', $project->getId()),
            $productivityAiService->analyzeProjectRisk($project, $assignments)
        );
        $request->getSession()->set(
            sprintf('project_ai_balance_%d', $project->getId()),
            $productivityAiService->balanceProjectWorkload($project, $assignments)
        );
        $request->getSession()->set(
            sprintf('project_ai_quality_%d', $project->getId()),
            $productivityAiService->generateQualityGate($project, $assignments)
        );
        $request->getSession()->set(
            sprintf('project_ai_sprint_%d', $project->getId()),
            $productivityAiService->generateWeeklySprintPlan($project, $assignments)
        );

        $this->addFlash('success', 'AI toolkit generated: Risk Radar, Workload Balance, Quality Gate, Weekly Sprint.');
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/ai-risk/apply', name: 'app_project_ai_risk_apply', methods: ['POST'])]
    public function applyAiRiskTasks(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('apply_project_ai_risk_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid risk apply request.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $risk = $request->getSession()->get(sprintf('project_ai_risk_%d', $project->getId()), []);
        $tasks = is_array($risk) && isset($risk['mitigation_tasks']) && is_array($risk['mitigation_tasks']) ? $risk['mitigation_tasks'] : [];
        if ($tasks === []) {
            $this->addFlash('warning', 'No mitigation tasks to apply. Generate AI toolkit first.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $created = $this->createAssignmentsFromTemplates($project, $tasks, $entityManager);
        $this->addFlash('success', sprintf('Risk Radar applied: %d mitigation task(s) created.', $created));
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/ai-quality/apply', name: 'app_project_ai_quality_apply', methods: ['POST'])]
    public function applyAiQualityTasks(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('apply_project_ai_quality_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid quality apply request.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $quality = $request->getSession()->get(sprintf('project_ai_quality_%d', $project->getId()), []);
        $tasks = is_array($quality) && isset($quality['qa_tasks']) && is_array($quality['qa_tasks']) ? $quality['qa_tasks'] : [];
        if ($tasks === []) {
            $this->addFlash('warning', 'No quality tasks to apply. Generate AI toolkit first.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $created = $this->createAssignmentsFromTemplates($project, $tasks, $entityManager);
        $this->addFlash('success', sprintf('Quality Gate applied: %d QA task(s) created.', $created));
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/ai-sprint/apply', name: 'app_project_ai_sprint_apply', methods: ['POST'])]
    public function applyAiSprintTasks(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('apply_project_ai_sprint_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid sprint apply request.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $sprint = $request->getSession()->get(sprintf('project_ai_sprint_%d', $project->getId()), []);
        $tasks = is_array($sprint) && isset($sprint['sprint_tasks']) && is_array($sprint['sprint_tasks']) ? $sprint['sprint_tasks'] : [];
        if ($tasks === []) {
            $this->addFlash('warning', 'No sprint tasks to apply. Generate AI toolkit first.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $created = $this->createAssignmentsFromTemplates($project, $tasks, $entityManager);
        $this->addFlash('success', sprintf('Weekly Sprint applied: %d sprint task(s) created.', $created));
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/ai-balance/apply', name: 'app_project_ai_balance_apply', methods: ['POST'])]
    public function applyAiBalance(
        Request $request,
        Project $project,
        AssignmentRepository $assignmentRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('apply_project_ai_balance_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid balance apply request.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $balance = $request->getSession()->get(sprintf('project_ai_balance_%d', $project->getId()), []);
        $adjustments = is_array($balance) && isset($balance['adjustments']) && is_array($balance['adjustments']) ? $balance['adjustments'] : [];
        if ($adjustments === []) {
            $this->addFlash('info', 'No workload adjustments to apply.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $byId = [];
        foreach ($assignmentRepository->findByProject($project) as $assignment) {
            $byId[$assignment->getId()] = $assignment;
        }

        $updated = 0;
        foreach ($adjustments as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int) ($item['assignment_id'] ?? 0);
            if ($id <= 0 || !isset($byId[$id])) {
                continue;
            }

            $assignment = $byId[$id];
            $newStart = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($item['new_start'] ?? ''));
            $newDue = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($item['new_due'] ?? ''));
            if (!$newStart || !$newDue) {
                continue;
            }
            if ($newDue < $newStart) {
                $newDue = $newStart;
            }

            $assignment->setDateDebut($newStart);
            $assignment->setDateFin($newDue);
            $updated++;
        }

        $entityManager->flush();
        $this->addFlash('success', sprintf('Workload Balancer applied: %d task date(s) updated.', $updated));
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/ai-suggestions/apply', name: 'app_project_ai_suggestions_apply', methods: ['POST'])]
    public function applyAiSuggestions(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('apply_project_ai_suggestions_' . $project->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid apply request.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $sessionKey = sprintf('project_ai_suggestions_%d', $project->getId());
        $suggestions = $request->getSession()->get($sessionKey, []);
        if (!is_array($suggestions) || $suggestions === []) {
            $this->addFlash('warning', 'No AI suggestions available. Generate first.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $selectedIndexes = $request->request->all('selected');
        $selectedIndexes = array_map('intval', is_array($selectedIndexes) ? $selectedIndexes : []);
        if ($selectedIndexes === []) {
            $this->addFlash('warning', 'Select at least one suggestion.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        $existingTitles = [];
        foreach ($project->getAssignments() as $existingAssignment) {
            $existingTitles[] = mb_strtolower(trim((string) $existingAssignment->getTitre()));
        }

        $created = 0;
        foreach ($selectedIndexes as $index) {
            if (!isset($suggestions[$index]) || !is_array($suggestions[$index])) {
                continue;
            }

            $item = $suggestions[$index];
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $normalizedTitle = mb_strtolower($title);
            if (in_array($normalizedTitle, $existingTitles, true)) {
                continue;
            }

            $assignment = new Assignment();
            $assignment->setUser($this->getUser());
            $assignment->setProject($project);
            $assignment->setTitre($title);
            $assignment->setDescription(trim((string) ($item['description'] ?? 'Generated by AI.')));
            $assignment->setPriorite((string) ($item['priority'] ?? 'Moyenne'));
            $assignment->setStatut((string) ($item['status'] ?? 'A faire'));

            $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($item['start_date'] ?? '')) ?: new \DateTimeImmutable('today');
            $dueDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($item['due_date'] ?? '')) ?: $startDate;
            if ($dueDate < $startDate) {
                $dueDate = $startDate;
            }

            $assignment->setDateDebut($startDate);
            $assignment->setDateFin($dueDate);

            $entityManager->persist($assignment);
            $existingTitles[] = $normalizedTitle;
            $created++;
        }

        $entityManager->flush();

        if ($created > 0) {
            $this->addFlash('success', sprintf('%d AI task(s) were added to this project.', $created));
        } else {
            $this->addFlash('info', 'No new tasks were created (possibly duplicates).');
        }

        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
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
            if (trim((string) $project->getTitre()) === '') {
                $this->addFlash('error', 'Project title is required.');
                return $this->render('pages/projects/edit.html.twig', [
                    'project' => $project,
                    'form' => $form,
                ]);
            }

            if ($project->getDateFin() === null) {
                $project->setDateFin($project->getDateDebut() ?? new \DateTimeImmutable('today'));
            }
            if ($project->getDateDebut() === null) {
                $project->setDateDebut($project->getDateFin() ?? new \DateTimeImmutable('today'));
            }

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
        NotificationManager $notificationManager
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

        $notificationManager->createNotification(
            $userToShare,
            'Project shared with you',
            sprintf('%s shared "%s" with you.', $this->getUser()->getFullName() ?? $this->getUser()->getEmail(), $project->getTitre()),
            'project_shared',
            $this->generateUrl('app_project_show', ['id' => $project->getId()])
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

    /**
     * @param array<int,array<string,mixed>> $templates
     */
    private function createAssignmentsFromTemplates(Project $project, array $templates, EntityManagerInterface $entityManager): int
    {
        $existingTitles = [];
        foreach ($project->getAssignments() as $existingAssignment) {
            $existingTitles[] = mb_strtolower(trim((string) $existingAssignment->getTitre()));
        }

        $created = 0;
        foreach ($templates as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $normalizedTitle = mb_strtolower($title);
            if (in_array($normalizedTitle, $existingTitles, true)) {
                continue;
            }

            $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($item['start_date'] ?? '')) ?: \DateTimeImmutable::createFromInterface($project->getDateDebut() ?? new \DateTimeImmutable('today'));
            $dueDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($item['due_date'] ?? '')) ?: $startDate;
            if ($dueDate < $startDate) {
                $dueDate = $startDate;
            }

            $assignment = new Assignment();
            $assignment->setUser($this->getUser());
            $assignment->setProject($project);
            $assignment->setTitre($title);
            $assignment->setDescription(trim((string) ($item['description'] ?? 'Generated by AI toolkit.')));
            $assignment->setPriorite((string) ($item['priority'] ?? 'Moyenne'));
            $assignment->setStatut((string) ($item['status'] ?? 'A faire'));
            $assignment->setDateDebut($startDate);
            $assignment->setDateFin($dueDate);

            $entityManager->persist($assignment);
            $existingTitles[] = $normalizedTitle;
            $created++;
        }

        $entityManager->flush();

        return $created;
    }

    private function isDoneStatus(string $status): bool
    {
        $normalized = mb_strtolower(trim($status));
        $normalized = str_replace(['Ã©', 'Ã¨', 'Ãª', 'é', 'è', 'ê'], 'e', $normalized);
        return in_array($normalized, ['termine', 'completed', 'done'], true);
    }
}
