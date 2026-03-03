<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductivityAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $geminiApiKey = ''
    ) {
    }

    /**
     * @param Assignment[] $assignments
     * @param Project[] $projects
     * @return array{summary:string,recommendations:array<int,array<string,mixed>>,source:string}
     */
    public function generateProductivityPlan(User $user, array $assignments, array $projects, int $userLevel): array
    {
        $fallback = $this->buildHeuristicPlan($assignments, $projects, $userLevel);

        if ($this->geminiApiKey === '') {
            return $fallback + ['source' => 'fallback'];
        }

        try {
            $assignmentData = array_map(fn (Assignment $a) => [
                'id' => $a->getId(),
                'title' => $a->getTitre(),
                'priority' => $a->getPriorite(),
                'status' => $a->getStatut(),
                'dueDate' => $a->getDateFin()?->format('Y-m-d'),
                'description' => mb_substr((string) $a->getDescription(), 0, 160),
            ], array_slice($assignments, 0, 12));

            $projectData = array_map(fn (Project $p) => [
                'id' => $p->getId(),
                'title' => $p->getTitre(),
                'status' => $p->getStatut(),
                'startDate' => $p->getDateDebut()?->format('Y-m-d'),
                'dueDate' => $p->getDateFin()?->format('Y-m-d'),
                'description' => mb_substr((string) $p->getDescription(), 0, 160),
            ], array_slice($projects, 0, 8));

            $prompt = $this->buildPrompt($user, $userLevel, $assignmentData, $projectData);

            $response = $this->httpClient->request('POST', sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=%s',
                urlencode($this->geminiApiKey)
            ), [
                'json' => [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => $prompt,
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'responseMimeType' => 'application/json',
                    ],
                ],
                'timeout' => 25,
            ]);

            $data = $response->toArray(false);
            $rawText = (string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
            $parsed = $this->decodeJsonObject($rawText);

            if (!is_array($parsed)) {
                return $fallback + ['source' => 'fallback'];
            }

            $recommendations = $parsed['recommendations'] ?? null;
            if (!is_array($recommendations) || $recommendations === []) {
                return $fallback + ['source' => 'fallback'];
            }

            return [
                'summary' => (string) ($parsed['summary'] ?? $fallback['summary']),
                'recommendations' => $this->normalizeRecommendations($recommendations, $userLevel, $assignments, $projects),
                'source' => 'gemini',
            ];
        } catch (\Throwable) {
            return $fallback + ['source' => 'fallback'];
        }
    }

    /**
     * Generate AI task suggestions for a specific project.
     *
     * @return array<int,array{title:string,description:string,priority:string,status:string,start_date:string,due_date:string,reason:string}>
     */
    public function generateAssignmentsForProject(User $user, Project $project, int $teamSize = 1, array $teamProfiles = []): array
    {
        $teamSize = max(1, $teamSize);
        $teamProfiles = $this->normalizeTeamProfiles($teamProfiles, $teamSize, $project);
        $projectSignals = $this->inferProjectSignals($project);
        [$minTasks, $maxTasks] = $this->getSuggestedTaskRange($project, $teamSize, $projectSignals['complexity']);
        $fallback = $this->buildProjectAssignmentFallback($project, $teamSize, $teamProfiles);

        if ($this->geminiApiKey === '') {
            return $fallback;
        }

        try {
            $prompt = sprintf(
                <<<PROMPT
You are a project planning assistant for students.
User: %s
Project title: %s
Project description: %s
Project start: %s
Project end: %s
Team size (number of users): %d
Team members and functional roles: %s
Project domain signals: %s
Complexity: %s
Project concept: %s
Expected concrete outputs: %s

Return ONLY valid JSON (no markdown), as an array of %d to %d task objects:
[
  {
    "title": "short task title",
    "description": "clear and actionable task description",
    "priority": "Haute|Moyenne|Basse",
    "status": "A faire|En cours",
    "start_date": "YYYY-MM-DD",
    "due_date": "YYYY-MM-DD",
    "reason": "why this task matters for project delivery"
  }
]

Rules:
- Keep task titles concise and unique.
- Dates must be within project start/end range.
- Include at least one high-priority and one medium-priority task.
- Tasks should represent a realistic execution order.
- Tasks must be logically adapted to title + description + team size.
- For team size >= 3 include at least 2 coordination tasks (handoff/review/integration).
- For team size = 1 avoid coordination overhead and focus on execution.
- Every task must mention a concrete deliverable (document, screen, API endpoint, model, report section, test plan, etc.).
- Add assignee_role and assignee_email fields for each task, chosen from provided team members.
- Never use dataset/model/training wording unless the project clearly belongs to data-analysis/ML.
PROMPT,
                (string) ($user->getEmail() ?? 'student'),
                (string) $project->getTitre(),
                (string) $project->getDescription(),
                $project->getDateDebut()?->format('Y-m-d') ?? (new \DateTimeImmutable('today'))->format('Y-m-d'),
                $project->getDateFin()?->format('Y-m-d') ?? (new \DateTimeImmutable('today'))->format('Y-m-d'),
                $teamSize,
                json_encode($teamProfiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                implode(', ', $projectSignals['domains']),
                $projectSignals['complexity'],
                (string) ($projectSignals['concept'] ?? 'General project delivery'),
                implode(', ', $projectSignals['outputs'] ?? []),
                $minTasks,
                $maxTasks,
            );

            $response = $this->httpClient->request('POST', sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=%s',
                urlencode($this->geminiApiKey)
            ), [
                'json' => [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => $prompt,
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.35,
                        'responseMimeType' => 'application/json',
                    ],
                ],
                'timeout' => 25,
            ]);

            $data = $response->toArray(false);
            $rawText = (string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
            $decoded = json_decode(trim($rawText), true);
            if (!is_array($decoded)) {
                return $fallback;
            }

            $normalized = $this->normalizeProjectAssignmentSuggestions($decoded, $project, $teamSize, $teamProfiles);
            if ($normalized === []) {
                return $fallback;
            }

            if (count($normalized) < $minTasks) {
                $existing = array_map(
                    static fn (array $item): string => mb_strtolower(trim((string) ($item['title'] ?? ''))),
                    $normalized
                );
                foreach ($fallback as $candidate) {
                    $titleKey = mb_strtolower(trim((string) ($candidate['title'] ?? '')));
                    if ($titleKey === '' || in_array($titleKey, $existing, true)) {
                        continue;
                    }
                    $normalized[] = $candidate;
                    $existing[] = $titleKey;
                    if (count($normalized) >= $minTasks) {
                        break;
                    }
                }
            }

            return $this->applyTeamRoleDistribution(array_slice($normalized, 0, $maxTasks), $teamProfiles);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @param Assignment[] $assignments
     * @return array{
     *   score:int,
     *   level:string,
     *   factors:array<int,string>,
     *   mitigation_tasks:array<int,array{title:string,description:string,priority:string,status:string,start_date:string,due_date:string,reason:string}>
     * }
     */
    public function analyzeProjectRisk(Project $project, array $assignments): array
    {
        $today = new \DateTimeImmutable('today');
        $end = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $today);
        $daysLeft = (int) $today->diff($end)->format('%r%a');
        $signals = $this->inferProjectSignals($project);
        $concept = (string) ($signals['concept'] ?? (string) $project->getTitre());
        $outputs = is_array($signals['outputs'] ?? null) ? $signals['outputs'] : ['deliverable'];
        $primaryOutput = (string) ($outputs[0] ?? 'deliverable');

        $total = count($assignments);
        $done = 0;
        $overdue = 0;
        $highPriorityOpen = 0;
        foreach ($assignments as $assignment) {
            if (!$assignment instanceof Assignment) {
                continue;
            }

            if ($this->isDoneStatus($assignment->getStatut())) {
                $done++;
            } else {
                $due = $assignment->getDateFin();
                if ($due instanceof \DateTimeInterface && \DateTimeImmutable::createFromInterface($due) < $today) {
                    $overdue++;
                }
                if ($assignment->getPriorite() === 'Haute') {
                    $highPriorityOpen++;
                }
            }
        }

        $progress = $total > 0 ? ($done / $total) : 0.0;
        $score = 20;
        if ($daysLeft <= 3) {
            $score += 25;
        } elseif ($daysLeft <= 7) {
            $score += 12;
        }
        $score += min(25, $overdue * 8);
        $score += min(20, $highPriorityOpen * 5);
        if ($progress < 0.35) {
            $score += 18;
        } elseif ($progress < 0.6) {
            $score += 8;
        }
        $score = max(0, min(100, $score));

        $level = $score >= 75 ? 'High' : ($score >= 45 ? 'Medium' : 'Low');

        $factors = [];
        if ($daysLeft <= 7) {
            $factors[] = sprintf('Deadline pressure: only %d day(s) left.', $daysLeft);
        }
        if ($overdue > 0) {
            $factors[] = sprintf('Overdue tasks detected: %d.', $overdue);
        }
        if ($highPriorityOpen > 0) {
            $factors[] = sprintf('Open high-priority tasks: %d.', $highPriorityOpen);
        }
        if ($progress < 0.6) {
            $factors[] = sprintf('Completion progress is %.0f%%, below safe delivery pace.', $progress * 100);
        }
        if ($factors === []) {
            $factors[] = 'No critical signals detected. Keep execution rhythm steady.';
        }
        if (($signals['complexity'] ?? 'medium') === 'high') {
            $factors[] = sprintf('Project complexity is high for "%s"; enforce tighter checkpoints.', $concept);
        }
        if (in_array('development', (array) ($signals['domains'] ?? []), true)) {
            $factors[] = 'Technical integration risk is present: validate interfaces before late-stage merge.';
        }
        if (in_array('data-analysis', (array) ($signals['domains'] ?? []), true)) {
            $factors[] = 'Data quality risk is present: validate source quality before final insights.';
        }

        $mitigation = [
            [
                'title' => sprintf('Risk triage for %s delivery', $primaryOutput),
                'description' => sprintf('List blockers impacting %s, assign owners, and remove the highest-impact blocker now.', $primaryOutput),
                'priority' => 'Haute',
                'status' => 'A faire',
                'start_date' => $today->format('Y-m-d'),
                'due_date' => $this->clampDate($today->modify('+1 day')->format('Y-m-d'), $today, $end)->format('Y-m-d'),
                'reason' => sprintf('Fast blocker removal protects delivery of %s.', $primaryOutput),
            ],
            [
                'title' => sprintf('Critical path alignment for %s', $concept),
                'description' => 'Reorder open tasks to keep only critical-path work active this week.',
                'priority' => 'Haute',
                'status' => 'A faire',
                'start_date' => $today->format('Y-m-d'),
                'due_date' => $this->clampDate($today->modify('+2 day')->format('Y-m-d'), $today, $end)->format('Y-m-d'),
                'reason' => 'Critical path focus reduces delay compounding.',
            ],
        ];

        return [
            'score' => $score,
            'level' => $level,
            'factors' => $factors,
            'mitigation_tasks' => $mitigation,
        ];
    }

    /**
     * @param Assignment[] $assignments
     * @return array{
     *   summary:string,
     *   adjustments:array<int,array{assignment_id:int,title:string,old_start:string,old_due:string,new_start:string,new_due:string,reason:string}>
     * }
     */
    public function balanceProjectWorkload(Project $project, array $assignments): array
    {
        $today = new \DateTimeImmutable('today');
        $projectStart = \DateTimeImmutable::createFromInterface($project->getDateDebut() ?? $today);
        $projectEnd = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $today);
        $signals = $this->inferProjectSignals($project);
        $concept = (string) ($signals['concept'] ?? (string) $project->getTitre());

        $open = array_values(array_filter($assignments, fn ($a) => $a instanceof Assignment && !$this->isDoneStatus($a->getStatut())));
        usort($open, function (Assignment $a, Assignment $b): int {
            $weight = ['Haute' => 0, 'Moyenne' => 1, 'Basse' => 2];
            $aw = $weight[$a->getPriorite() ?? 'Moyenne'] ?? 1;
            $bw = $weight[$b->getPriorite() ?? 'Moyenne'] ?? 1;
            if ($aw !== $bw) {
                return $aw <=> $bw;
            }
            $ad = $a->getDateFin()?->getTimestamp() ?? PHP_INT_MAX;
            $bd = $b->getDateFin()?->getTimestamp() ?? PHP_INT_MAX;
            return $ad <=> $bd;
        });
        usort($open, function (Assignment $a, Assignment $b): int {
            $laneWeight = ['scoping' => 0, 'design' => 1, 'build' => 2, 'validation' => 3, 'delivery' => 4, 'coordination' => 5];
            $la = $laneWeight[$this->classifyAssignmentLane($a)] ?? 2;
            $lb = $laneWeight[$this->classifyAssignmentLane($b)] ?? 2;
            if ($la !== $lb) {
                return $la <=> $lb;
            }
            return 0;
        });

        $cursor = $today > $projectStart ? $today : $projectStart;
        $adjustments = [];
        foreach ($open as $assignment) {
            $lane = $this->classifyAssignmentLane($assignment);
            $laneDurBoost = in_array($lane, ['build', 'validation'], true) ? 1 : 0;
            $durationDays = 1;
            if ($assignment->getDateDebut() instanceof \DateTimeInterface && $assignment->getDateFin() instanceof \DateTimeInterface) {
                $durationDays = max(1, (int) \DateTimeImmutable::createFromInterface($assignment->getDateDebut())->diff(\DateTimeImmutable::createFromInterface($assignment->getDateFin()))->days);
            }
            $durationDays = max(1, $durationDays + $laneDurBoost);

            $newStart = $this->clampDate($cursor->format('Y-m-d'), $projectStart, $projectEnd);
            $newDue = $this->clampDate($newStart->modify(sprintf('+%d day', $durationDays))->format('Y-m-d'), $projectStart, $projectEnd);
            if ($newDue < $newStart) {
                $newDue = $newStart;
            }

            $oldStart = $assignment->getDateDebut()?->format('Y-m-d') ?? $newStart->format('Y-m-d');
            $oldDue = $assignment->getDateFin()?->format('Y-m-d') ?? $newDue->format('Y-m-d');
            if ($oldStart !== $newStart->format('Y-m-d') || $oldDue !== $newDue->format('Y-m-d')) {
                $adjustments[] = [
                    'assignment_id' => (int) $assignment->getId(),
                    'title' => (string) $assignment->getTitre(),
                    'old_start' => $oldStart,
                    'old_due' => $oldDue,
                    'new_start' => $newStart->format('Y-m-d'),
                    'new_due' => $newDue->format('Y-m-d'),
                    'reason' => sprintf('Reordered in %s lane to reduce collisions for %s delivery.', $lane, $concept),
                ];
            }

            $cursor = $newDue->modify('+1 day');
        }

        return [
            'summary' => $adjustments === []
                ? sprintf('Current schedule is already balanced for %s.', $concept)
                : sprintf('Proposed %d date adjustment(s) to balance execution for %s.', count($adjustments), $concept),
            'adjustments' => $adjustments,
        ];
    }

    /**
     * @param Assignment[] $assignments
     * @return array{
     *   checklist:array<int,array{item:string,priority:string,reason:string}>,
     *   qa_tasks:array<int,array{title:string,description:string,priority:string,status:string,start_date:string,due_date:string,reason:string}>
     * }
     */
    public function generateQualityGate(Project $project, array $assignments): array
    {
        $today = new \DateTimeImmutable('today');
        $end = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $today);
        $qaStart = $this->clampDate($today->format('Y-m-d'), $today, $end);
        $qaDue = $this->clampDate($today->modify('+3 day')->format('Y-m-d'), $today, $end);
        $signals = $this->inferProjectSignals($project);
        $concept = (string) ($signals['concept'] ?? (string) $project->getTitre());
        $outputs = is_array($signals['outputs'] ?? null) ? $signals['outputs'] : ['deliverable'];

        $openCount = count(array_filter($assignments, fn ($a) => $a instanceof Assignment && !$this->isDoneStatus($a->getStatut())));
        $checklist = [
            ['item' => sprintf('Acceptance criteria validated for %s.', $outputs[0] ?? 'core deliverable'), 'priority' => 'Haute', 'reason' => 'Prevents ambiguous completion.'],
            ['item' => 'Core flow tested on happy path and failure path.', 'priority' => 'Haute', 'reason' => 'Reduces production regressions.'],
            ['item' => sprintf('Final output pack reviewed (%s).', implode(', ', array_slice($outputs, 0, 2))), 'priority' => 'Moyenne', 'reason' => 'Improves delivery confidence.'],
        ];
        foreach ($this->buildDomainSpecificQaChecks((array) ($signals['domains'] ?? []), $concept) as $extraCheck) {
            $checklist[] = $extraCheck;
        }
        if ($openCount > 3) {
            $checklist[] = ['item' => 'Scope freeze confirmed for remaining sprint.', 'priority' => 'Haute', 'reason' => 'Avoids late-stage scope creep.'];
        }

        $qaTasks = [
            [
                'title' => sprintf('Define acceptance checklist for %s', $concept),
                'description' => sprintf('Create final QA checklist and map each criterion to evidence for %s.', implode(', ', array_slice($outputs, 0, 2))),
                'priority' => 'Haute',
                'status' => 'A faire',
                'start_date' => $qaStart->format('Y-m-d'),
                'due_date' => $qaDue->format('Y-m-d'),
                'reason' => 'Locks quality target before final delivery.',
            ],
            [
                'title' => sprintf('Run QA validation pass for %s', $concept),
                'description' => 'Execute domain-specific checks and log corrective actions before submission.',
                'priority' => 'Moyenne',
                'status' => 'A faire',
                'start_date' => $qaStart->format('Y-m-d'),
                'due_date' => $qaDue->format('Y-m-d'),
                'reason' => 'Catches defects before final handoff.',
            ],
        ];

        return [
            'checklist' => $checklist,
            'qa_tasks' => $qaTasks,
        ];
    }

    /**
     * @param Assignment[] $assignments
     * @return array{
     *   week_label:string,
     *   focus:string,
     *   sprint_tasks:array<int,array{title:string,description:string,priority:string,status:string,start_date:string,due_date:string,reason:string}>
     * }
     */
    public function generateWeeklySprintPlan(Project $project, array $assignments): array
    {
        $today = new \DateTimeImmutable('today');
        $weekStart = $today->modify('monday this week');
        $weekEnd = $today->modify('sunday this week');
        $projectEnd = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $weekEnd);
        $signals = $this->inferProjectSignals($project);
        $concept = (string) ($signals['concept'] ?? (string) $project->getTitre());
        $outputs = is_array($signals['outputs'] ?? null) ? $signals['outputs'] : ['deliverable'];

        $openAssignments = array_values(array_filter($assignments, fn ($a) => $a instanceof Assignment && !$this->isDoneStatus($a->getStatut())));
        usort($openAssignments, fn (Assignment $a, Assignment $b) => ($a->getDateFin()?->getTimestamp() ?? PHP_INT_MAX) <=> ($b->getDateFin()?->getTimestamp() ?? PHP_INT_MAX));

        $focus = count($openAssignments) > 5
            ? sprintf('Stabilize %s by finishing highest-impact tasks first.', $concept)
            : sprintf('Close current tasks to secure %s readiness.', $outputs[0] ?? 'delivery');

        $tasks = [];
        $slotStart = $weekStart;
        foreach (array_slice($openAssignments, 0, 3) as $assignment) {
            $slotDue = $slotStart->modify('+2 day');
            $slotStart = $this->clampDate($slotStart->format('Y-m-d'), $today, $projectEnd);
            $slotDue = $this->clampDate($slotDue->format('Y-m-d'), $today, $projectEnd);
            if ($slotDue < $slotStart) {
                $slotDue = $slotStart;
            }

            $tasks[] = [
                'title' => 'Sprint: ' . (string) $assignment->getTitre(),
                'description' => sprintf('Focused completion block for this item and its contribution to %s.', $outputs[0] ?? 'final output'),
                'priority' => (string) ($assignment->getPriorite() ?? 'Moyenne'),
                'status' => 'A faire',
                'start_date' => $slotStart->format('Y-m-d'),
                'due_date' => $slotDue->format('Y-m-d'),
                'reason' => 'Weekly sprint sequencing to maximize throughput.',
            ];

            $slotStart = $slotDue->modify('+1 day');
        }

        if ($tasks === []) {
            $tasks[] = [
                'title' => sprintf('Sprint planning for %s', $concept),
                'description' => sprintf('Prepare next milestone and backlog focused on %s.', $outputs[0] ?? 'core output'),
                'priority' => 'Moyenne',
                'status' => 'A faire',
                'start_date' => $today->format('Y-m-d'),
                'due_date' => $this->clampDate($today->modify('+2 day')->format('Y-m-d'), $today, $projectEnd)->format('Y-m-d'),
                'reason' => 'Maintains momentum when backlog is light.',
            ];
        }

        return [
            'week_label' => sprintf('%s - %s', $weekStart->format('d M'), $weekEnd->format('d M')),
            'focus' => $focus,
            'sprint_tasks' => $tasks,
        ];
    }

    /**
     * @param Assignment[] $assignments
     * @return array{
     *   project_title:string,
     *   generated_at:string,
     *   executive_summary:string,
     *   achievements:array<int,string>,
     *   challenges:array<int,string>,
     *   lessons_learned:array<int,string>,
     *   next_steps:array<int,string>,
     *   metrics:array<string,mixed>
     * }
     */
    public function generateProjectCompletionReport(User $user, Project $project, array $assignments): array
    {
        $fallback = $this->buildCompletionReportFallback($project, $assignments);

        if ($this->geminiApiKey === '') {
            return $fallback;
        }

        try {
            $assignmentData = array_map(fn (Assignment $a) => [
                'title' => $a->getTitre(),
                'status' => $a->getStatut(),
                'priority' => $a->getPriorite(),
                'start' => $a->getDateDebut()?->format('Y-m-d'),
                'due' => $a->getDateFin()?->format('Y-m-d'),
            ], array_slice($assignments, 0, 30));
            $signals = $this->inferProjectSignals($project);

            $prompt = sprintf(
                <<<PROMPT
You are a project post-mortem assistant for students.
User: %s
Project title: %s
Project description: %s
Project start: %s
Project end: %s
Project concept: %s
Expected outputs: %s
Project domains: %s
Assignments: %s

Return ONLY valid JSON with this shape:
{
  "executive_summary": "short paragraph",
  "achievements": ["...","..."],
  "challenges": ["...","..."],
  "lessons_learned": ["...","..."],
  "next_steps": ["...","..."]
}

Rules:
- Be specific and practical.
- Mention delivery quality, timing, and process.
- Keep each bullet concise (one sentence).
PROMPT,
                (string) ($user->getEmail() ?? 'student'),
                (string) $project->getTitre(),
                (string) $project->getDescription(),
                $project->getDateDebut()?->format('Y-m-d') ?? '',
                $project->getDateFin()?->format('Y-m-d') ?? '',
                (string) ($signals['concept'] ?? (string) $project->getTitre()),
                implode(', ', (array) ($signals['outputs'] ?? [])),
                implode(', ', (array) ($signals['domains'] ?? [])),
                json_encode($assignmentData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $response = $this->httpClient->request('POST', sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=%s',
                urlencode($this->geminiApiKey)
            ), [
                'json' => [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => $prompt,
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.35,
                        'responseMimeType' => 'application/json',
                    ],
                ],
                'timeout' => 25,
            ]);

            $data = $response->toArray(false);
            $rawText = (string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
            $parsed = $this->decodeJsonObject($rawText);
            if (!is_array($parsed)) {
                return $fallback;
            }

            return [
                'project_title' => (string) $project->getTitre(),
                'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'executive_summary' => trim((string) ($parsed['executive_summary'] ?? $fallback['executive_summary'])),
                'achievements' => $this->normalizeStringList($parsed['achievements'] ?? $fallback['achievements']),
                'challenges' => $this->normalizeStringList($parsed['challenges'] ?? $fallback['challenges']),
                'lessons_learned' => $this->normalizeStringList($parsed['lessons_learned'] ?? $fallback['lessons_learned']),
                'next_steps' => $this->normalizeStringList($parsed['next_steps'] ?? $fallback['next_steps']),
                'metrics' => $fallback['metrics'],
            ];
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @param array<int,array<string,mixed>> $recommendations
     * @return array<int,array<string,mixed>>
     */
    private function normalizeRecommendations(array $recommendations, int $userLevel, array $assignments = [], array $projects = []): array
    {
        $normalized = [];
        $assignmentById = [];
        $projectById = [];
        $openAssignmentsByProject = $this->buildOpenAssignmentsCountByProject($assignments);
        foreach ($assignments as $assignment) {
            if ($assignment instanceof Assignment && $assignment->getId() !== null) {
                $assignmentById[(int) $assignment->getId()] = $assignment;
            }
        }
        foreach ($projects as $project) {
            if ($project instanceof Project && $project->getId() !== null) {
                $projectById[(int) $project->getId()] = $project;
            }
        }

        foreach ($recommendations as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = in_array(($item['type'] ?? ''), ['assignment', 'project'], true) ? $item['type'] : 'assignment';
            $targetId = (int) ($item['target_id'] ?? 0);
            if ($targetId <= 0) {
                continue;
            }

            $aiEstimated = max(20, (int) ($item['estimated_minutes'] ?? 0));
            $smartEstimated = $aiEstimated > 0 ? $aiEstimated : 60;
            $smartComplexity = 'medium';
            if ($type === 'assignment' && isset($assignmentById[$targetId])) {
                $assignment = $assignmentById[$targetId];
                $smartEstimated = $this->estimateAssignmentMinutes($assignment, $userLevel);
                $smartComplexity = $this->inferAssignmentComplexity($assignment);
            } elseif ($type === 'project' && isset($projectById[$targetId])) {
                $project = $projectById[$targetId];
                $openCount = (int) ($openAssignmentsByProject[$targetId] ?? 0);
                $smartEstimated = $this->estimateProjectMinutes($project, $userLevel, $openCount);
                $smartComplexity = (string) ($this->inferProjectSignals($project)['complexity'] ?? 'medium');
            }

            $estimatedMinutes = $aiEstimated > 0
                ? (int) round(($smartEstimated * 0.7) + ($aiEstimated * 0.3))
                : $smartEstimated;
            $estimatedMinutes = max(20, min(480, $estimatedMinutes));

            $challengeRatio = $this->resolveChallengeRatio($userLevel, $smartComplexity);
            $challengeMinutes = max(15, (int) round($estimatedMinutes * $challengeRatio));
            if ($challengeMinutes >= $estimatedMinutes) {
                $challengeMinutes = max(15, $estimatedMinutes - 5);
            }

            $rewardCoins = max(20, (int) ($item['reward_coins'] ?? ($challengeMinutes / 4.0 + $userLevel * 3.2)));
            $rewardCoins = (int) round($rewardCoins * ($smartComplexity === 'high' ? 1.1 : ($smartComplexity === 'low' ? 0.9 : 1.0)));

            $tips = $item['tips'] ?? [];
            if (!is_array($tips) || $tips === []) {
                $tips = [
                    'Define the single hardest deliverable first, then attack it in sprint 1.',
                    'Run a strict 30-minute deep-work sprint, then a 5-minute break.',
                ];
            }

            $normalized[] = [
                'type' => $type,
                'target_id' => $targetId,
                'title' => trim((string) ($item['title'] ?? 'Untitled target')),
                'estimated_minutes' => $estimatedMinutes,
                'challenge_minutes' => $challengeMinutes,
                'reward_coins' => min(250, $rewardCoins),
                'reason' => trim((string) ($item['reason'] ?? 'Critical bottleneck detected: finish the highest-impact block first.')),
                'tips' => array_values(array_map(static fn ($tip) => (string) $tip, array_slice($tips, 0, 4))),
                'set_status' => trim((string) ($item['set_status'] ?? 'En cours')),
            ];
        }

        return $normalized;
    }

    /**
     * @param Assignment[] $assignments
     * @param Project[] $projects
     * @return array{summary:string,recommendations:array<int,array<string,mixed>>}
     */
    private function buildHeuristicPlan(array $assignments, array $projects, int $userLevel): array
    {
        $recommendations = [];
        $openAssignmentsByProject = $this->buildOpenAssignmentsCountByProject($assignments);

        usort($assignments, static function (Assignment $a, Assignment $b): int {
            $aTs = $a->getDateFin()?->getTimestamp() ?? PHP_INT_MAX;
            $bTs = $b->getDateFin()?->getTimestamp() ?? PHP_INT_MAX;
            return $aTs <=> $bTs;
        });

        foreach (array_slice($assignments, 0, 3) as $assignment) {
            $estimatedMinutes = $this->estimateAssignmentMinutes($assignment, $userLevel);
            $challengeMinutes = max(20, (int) round($estimatedMinutes * $this->resolveChallengeRatio($userLevel, $this->inferAssignmentComplexity($assignment))));
            if ($challengeMinutes >= $estimatedMinutes) {
                $challengeMinutes = max(20, $estimatedMinutes - 5);
            }
            $rewardCoins = min(260, max(25, (int) round($challengeMinutes / 3.8 + $userLevel * 3.1)));
            $reason = $this->buildAssignmentReason($assignment);

            $recommendations[] = [
                'type' => 'assignment',
                'target_id' => $assignment->getId(),
                'title' => (string) $assignment->getTitre(),
                'estimated_minutes' => $estimatedMinutes,
                'challenge_minutes' => $challengeMinutes,
                'reward_coins' => $rewardCoins,
                'reason' => $reason,
                'tips' => $this->buildAssignmentTips($assignment),
                'set_status' => 'En cours',
            ];
        }

        foreach (array_slice($projects, 0, 2) as $project) {
            $projectId = (int) ($project->getId() ?? 0);
            $openCount = (int) ($openAssignmentsByProject[$projectId] ?? 0);
            $signals = $this->inferProjectSignals($project);
            $estimatedMinutes = $this->estimateProjectMinutes($project, $userLevel, $openCount);
            $challengeMinutes = max(30, (int) round($estimatedMinutes * $this->resolveChallengeRatio($userLevel, (string) ($signals['complexity'] ?? 'medium'))));
            if ($challengeMinutes >= $estimatedMinutes) {
                $challengeMinutes = max(30, $estimatedMinutes - 5);
            }
            $rewardCoins = min(320, max(40, (int) round($challengeMinutes / 3.7 + $userLevel * 4.3)));

            $recommendations[] = [
                'type' => 'project',
                'target_id' => $project->getId(),
                'title' => (string) $project->getTitre(),
                'estimated_minutes' => $estimatedMinutes,
                'challenge_minutes' => $challengeMinutes,
                'reward_coins' => $rewardCoins,
                'reason' => $this->buildProjectReason($project),
                'tips' => $this->buildProjectTips($project),
                'set_status' => 'En cours',
            ];
        }

        return [
            'summary' => 'AI plan generated from your current workload and level. Focus on high-impact tasks first.',
            'recommendations' => array_slice($recommendations, 0, 6),
        ];
    }

    /**
     * @param Assignment[] $assignments
     * @return array<int,int>
     */
    private function buildOpenAssignmentsCountByProject(array $assignments): array
    {
        $countByProject = [];
        foreach ($assignments as $assignment) {
            if (!$assignment instanceof Assignment || $this->isDoneStatus($assignment->getStatut())) {
                continue;
            }
            $projectId = (int) ($assignment->getProject()?->getId() ?? 0);
            if ($projectId <= 0) {
                continue;
            }
            $countByProject[$projectId] = (int) (($countByProject[$projectId] ?? 0) + 1);
        }

        return $countByProject;
    }

    private function estimateAssignmentMinutes(Assignment $assignment, int $userLevel): int
    {
        $priority = mb_strtolower((string) $assignment->getPriorite());
        $base = match ($priority) {
            'haute', 'high' => 120,
            'moyenne', 'medium' => 85,
            default => 60,
        };
        $daysToDue = $this->daysToDate($assignment->getDateFin());
        $urgencyMultiplier = $daysToDue <= 1 ? 1.22 : ($daysToDue <= 3 ? 1.12 : 1.0);
        $desc = mb_strtolower((string) $assignment->getDescription());
        $textLengthBoost = mb_strlen($desc) > 220 ? 20 : (mb_strlen($desc) > 90 ? 10 : 0);
        $complexBoost = 0;
        if (preg_match('/integration|architecture|algorithm|security|optimization|performance|analytics|model/', $desc)) {
            $complexBoost += 18;
        }
        if (preg_match('/report|presentation|documentation|testing|validation/', $desc)) {
            $complexBoost += 8;
        }
        $projectComplexity = $this->inferAssignmentComplexity($assignment);
        if ($projectComplexity === 'high') {
            $complexBoost += 14;
        } elseif ($projectComplexity === 'low') {
            $complexBoost -= 6;
        }

        $skillMultiplier = $this->resolveSkillMultiplier($userLevel);
        $estimate = (int) round(($base + $textLengthBoost + $complexBoost) * $urgencyMultiplier * $skillMultiplier);

        return max(25, min(300, $estimate));
    }

    private function estimateProjectMinutes(Project $project, int $userLevel, int $openAssignmentsCount = 0): int
    {
        $signals = $this->inferProjectSignals($project);
        $complexity = (string) ($signals['complexity'] ?? 'medium');
        $domains = is_array($signals['domains'] ?? null) ? $signals['domains'] : [];
        $spanDays = max(1, (int) \DateTimeImmutable::createFromInterface($project->getDateDebut() ?? new \DateTimeImmutable('today'))
            ->diff(\DateTimeImmutable::createFromInterface($project->getDateFin() ?? new \DateTimeImmutable('today')))->days);
        $daysToDue = $this->daysToDate($project->getDateFin());

        $base = 95;
        $base += $complexity === 'high' ? 55 : ($complexity === 'low' ? 10 : 30);
        $base += min(45, (int) round($spanDays * 0.8));
        $base += min(45, max(0, $openAssignmentsCount - 2) * 8);
        $base += count($domains) >= 3 ? 16 : (count($domains) === 2 ? 8 : 0);

        if (in_array('development', $domains, true)) {
            $base += 14;
        }
        if (in_array('data-analysis', $domains, true)) {
            $base += 12;
        }
        if (in_array('design', $domains, true)) {
            $base += 8;
        }

        $urgencyMultiplier = $daysToDue <= 2 ? 1.2 : ($daysToDue <= 7 ? 1.1 : 1.0);
        $skillMultiplier = $this->resolveSkillMultiplier($userLevel);
        $estimate = (int) round($base * $urgencyMultiplier * $skillMultiplier);

        return max(45, min(420, $estimate));
    }

    private function inferAssignmentComplexity(Assignment $assignment): string
    {
        $project = $assignment->getProject();
        if ($project instanceof Project) {
            return (string) ($this->inferProjectSignals($project)['complexity'] ?? 'medium');
        }
        return 'medium';
    }

    private function resolveSkillMultiplier(int $userLevel): float
    {
        $level = max(1, $userLevel);
        $multiplier = 1.20 - min(0.42, $level * 0.03);
        return max(0.72, min(1.22, $multiplier));
    }

    private function resolveChallengeRatio(int $userLevel, string $complexity): float
    {
        $base = 0.66 + min(0.14, max(1, $userLevel) * 0.01);
        if ($complexity === 'high') {
            $base -= 0.03;
        } elseif ($complexity === 'low') {
            $base += 0.02;
        }

        return max(0.60, min(0.82, $base));
    }

    /**
     * @param array<int,array<string,mixed>> $assignments
     * @param array<int,array<string,mixed>> $projects
     */
    private function buildPrompt(User $user, int $userLevel, array $assignments, array $projects): string
    {
        return sprintf(
            <<<PROMPT
You are a productivity coach for a student app.
User: %s
Level: %d

Assignments: %s
Projects: %s

Return ONLY valid JSON (no markdown) with this shape:
{
  "summary": "one short paragraph",
  "recommendations": [
    {
      "type": "assignment|project",
      "target_id": 123,
      "title": "task/project title",
      "estimated_minutes": 90,
      "challenge_minutes": 70,
      "reward_coins": 55,
      "set_status": "En cours",
      "reason": "short reason",
      "tips": ["tip1","tip2","tip3"]
    }
  ]
}

Rules:
- Max 6 recommendations.
- Recommendations must reference existing target_id values from provided data.
- Estimated and challenge minutes must be realistic.
- Challenge minutes should be less than estimated_minutes and feel ambitious.
- Use assertive language for challenge framing.
- Reason must explicitly state the task or project bottleneck/problem.
- Tips must be concrete and directly solve that bottleneck/problem.
PROMPT,
            $user->getEmail() ?? 'student',
            max(1, $userLevel),
            json_encode($assignments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function daysToDate(?\DateTimeInterface $date): int
    {
        if (!$date) {
            return 30;
        }

        $today = new \DateTimeImmutable('today');
        $target = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0);
        return (int) $today->diff($target)->format('%r%a');
    }

    private function buildAssignmentReason(Assignment $assignment): string
    {
        $daysToDue = $this->daysToDate($assignment->getDateFin());
        $priority = mb_strtolower((string) $assignment->getPriorite());
        $desc = mb_strtolower((string) $assignment->getDescription());

        if ($daysToDue < 0) {
            return 'Problem: this task is already overdue and needs immediate recovery work.';
        }

        if ($daysToDue <= 1) {
            return 'Problem: deadline pressure is high, so this task needs an aggressive finish window now.';
        }

        if (in_array($priority, ['haute', 'high'], true)) {
            return 'Problem: high-priority scope can block multiple downstream tasks if delayed.';
        }

        if (str_contains($desc, 'research') || str_contains($desc, 'analyse') || str_contains($desc, 'analysis')) {
            return 'Problem: analysis scope is broad; you must constrain and finalize key findings fast.';
        }

        return 'Problem: progress drift risk is rising; complete a concrete deliverable before context is lost.';
    }

    /**
     * @return array<int,string>
     */
    private function buildAssignmentTips(Assignment $assignment): array
    {
        $daysToDue = $this->daysToDate($assignment->getDateFin());
        $desc = mb_strtolower((string) $assignment->getDescription());
        $tips = [];

        if ($daysToDue <= 1) {
            $tips[] = 'Lock a hard stop and ship a minimum complete version first.';
            $tips[] = 'Skip low-value polish; finish core requirements before extras.';
        }

        if (str_contains($desc, 'presentation') || str_contains($desc, 'slides')) {
            $tips[] = 'Finalize your storyline first, then build slides around 3 key messages.';
        } elseif (str_contains($desc, 'report') || str_contains($desc, 'essay')) {
            $tips[] = 'Write the structure first (intro, 3 sections, conclusion), then fill details.';
        } elseif (str_contains($desc, 'code') || str_contains($desc, 'implement') || str_contains($desc, 'dev')) {
            $tips[] = 'Implement the riskiest module first and test it before expanding scope.';
        } else {
            $tips[] = 'Break work into 3 measurable subtasks and complete subtask 1 in the first sprint.';
        }

        $tips[] = 'Run focused sprints: 30 minutes deep work + 5 minutes reset, no notifications.';

        return array_slice(array_values(array_unique($tips)), 0, 4);
    }

    private function buildProjectReason(Project $project): string
    {
        $daysToDue = $this->daysToDate($project->getDateFin());
        $status = mb_strtolower((string) $project->getStatut());

        if ($daysToDue < 0) {
            return 'Problem: this project is overdue and requires immediate milestone triage.';
        }

        if ($daysToDue <= 2) {
            return 'Problem: the project deadline is very close; only critical path actions should remain.';
        }

        if (in_array($status, ['en attente', 'pending'], true)) {
            return 'Problem: project is stalled; unblock execution by defining one milestone and one owner now.';
        }

        return 'Problem: project momentum is fragile; sharpen the next milestone to prevent scope drift.';
    }

    /**
     * @return array<int,string>
     */
    private function buildProjectTips(Project $project): array
    {
        $daysToDue = $this->daysToDate($project->getDateFin());
        $tips = [
            'Define one milestone that is achievable within this challenge window.',
            'List blockers, then remove one blocker before adding new tasks.',
            'Keep only critical-path tasks active until milestone completion.',
        ];

        if ($daysToDue <= 2) {
            array_unshift($tips, 'Cut non-essential scope now and focus only on what must ship by deadline.');
        }

        return array_slice(array_values(array_unique($tips)), 0, 4);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function decodeJsonObject(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $json = substr($raw, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<int,mixed> $items
     * @return array<int,array{title:string,description:string,priority:string,status:string,start_date:string,due_date:string,reason:string}>
     */
    private function normalizeProjectAssignmentSuggestions(array $items, Project $project, int $teamSize = 1, array $teamProfiles = []): array
    {
        $normalized = [];
        $start = \DateTimeImmutable::createFromInterface($project->getDateDebut() ?? new \DateTimeImmutable('today'));
        $end = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $start);
        $seenTitles = [];
        $signals = $this->inferProjectSignals($project);
        [$minTasks, $maxTasks] = $this->getSuggestedTaskRange($project, max(1, $teamSize), (string) ($signals['complexity'] ?? 'medium'));
        $concept = (string) ($signals['concept'] ?? (string) $project->getTitre());
        $outputs = is_array($signals['outputs'] ?? null) ? $signals['outputs'] : ['deliverable'];

        foreach (array_slice($items, 0, $maxTasks + 2) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $normalizedTitle = mb_strtolower($title);
            if (in_array($normalizedTitle, $seenTitles, true)) {
                continue;
            }

            $description = trim((string) ($item['description'] ?? 'Define and deliver this task.'));
            $priority = (string) ($item['priority'] ?? 'Moyenne');
            if (!in_array($priority, ['Haute', 'Moyenne', 'Basse'], true)) {
                $priority = 'Moyenne';
            }

            $status = (string) ($item['status'] ?? 'A faire');
            if (!in_array($status, ['A faire', 'En cours'], true)) {
                $status = 'A faire';
            }

            $startDate = $this->clampDate((string) ($item['start_date'] ?? $start->format('Y-m-d')), $start, $end);
            $dueDate = $this->clampDate((string) ($item['due_date'] ?? $end->format('Y-m-d')), $start, $end);
            if ($dueDate < $startDate) {
                $dueDate = $startDate;
            }

            $contextual = $this->contextualizeTaskContent($title, $description, $concept, $outputs, $project);

            $normalized[] = [
                'title' => $contextual['title'],
                'description' => $contextual['description'],
                'priority' => $priority,
                'status' => $status,
                'start_date' => $startDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'reason' => trim((string) ($item['reason'] ?? sprintf('This task secures a concrete output for %s.', $concept))),
                'assignee_role' => trim((string) ($item['assignee_role'] ?? '')),
                'assignee_email' => trim((string) ($item['assignee_email'] ?? '')),
            ];
            $seenTitles[] = $normalizedTitle;
        }

        usort($normalized, static function (array $a, array $b): int {
            return strcmp((string) ($a['start_date'] ?? ''), (string) ($b['start_date'] ?? ''));
        });

        if (count($normalized) > $maxTasks) {
            $normalized = array_slice($normalized, 0, $maxTasks);
        }

        if (count($normalized) < $minTasks) {
            return $normalized;
        }

        return $this->applyTeamRoleDistribution($normalized, $teamProfiles);
    }

    /**
     * @return array<int,array{title:string,description:string,priority:string,status:string,start_date:string,due_date:string,reason:string}>
     */
    private function buildProjectAssignmentFallback(Project $project, int $teamSize = 1, array $teamProfiles = []): array
    {
        $start = \DateTimeImmutable::createFromInterface($project->getDateDebut() ?? new \DateTimeImmutable('today'));
        $end = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $start);
        $spanDays = max(1, (int) $start->diff($end)->days);
        $signals = $this->inferProjectSignals($project);
        [$minTasks, $maxTasks] = $this->getSuggestedTaskRange($project, max(1, $teamSize), $signals['complexity']);

        $projectFocus = $this->resolveProjectFocusLabel($signals['domains']);
        $primaryDomain = $this->resolvePrimaryDomain($signals['domains']);
        $concept = (string) ($signals['concept'] ?? (string) $project->getTitre());
        $outputs = is_array($signals['outputs'] ?? null) ? $signals['outputs'] : ['deliverable'];
        $teamMode = $teamSize >= 5 ? 'multi-team' : ($teamSize >= 3 ? 'team' : ($teamSize === 2 ? 'pair' : 'solo'));

        $phases = [
            [
                'title' => sprintf('Clarify scope and acceptance criteria for %s', $concept),
                'description' => sprintf('Define objectives, constraints, and acceptance criteria; produce a scope brief for %s.', $concept),
                'priority' => 'Haute',
                'reason' => 'A clear scope prevents rework and keeps delivery aligned.',
            ],
        ];
        $phases = array_merge($phases, $this->buildDomainExecutionPhases($primaryDomain, $concept, $outputs, $projectFocus));

        if ($teamMode !== 'solo') {
            $phases[] = [
                'title' => 'Team sync and handoff checkpoints',
                'description' => sprintf('Organize %s coordination checkpoints and define clear handoffs between members.', $teamMode === 'multi-team' ? 'cross-team' : 'team'),
                'priority' => 'Moyenne',
                'reason' => 'Explicit handoffs reduce delays and merge conflicts.',
            ];
        }
        if ($primaryDomain === 'development') {
            $phases[] = [
                'title' => 'Integration testing and bug fixing',
                'description' => 'Execute integration tests, fix priority defects, and secure release readiness.',
                'priority' => 'Haute',
                'reason' => 'Integration failures are the main risk before delivery.',
            ];
        }
        if ($primaryDomain === 'data-analysis') {
            $phases[] = [
                'title' => 'Data validation and insights synthesis',
                'description' => 'Validate dataset quality, run final analysis, and synthesize actionable insights.',
                'priority' => 'Haute',
                'reason' => 'Data quality directly impacts result credibility.',
            ];
        }
        if ($primaryDomain === 'design') {
            $phases[] = [
                'title' => 'Design review and consistency pass',
                'description' => 'Review visual consistency, accessibility, and final design polish.',
                'priority' => 'Moyenne',
                'reason' => 'Consistency improves usability and perceived quality.',
            ];
        }
        if ($primaryDomain === 'research') {
            $phases[] = [
                'title' => 'Literature synthesis and argument mapping',
                'description' => 'Consolidate sources, structure findings, and align arguments with objectives.',
                'priority' => 'Moyenne',
                'reason' => 'Structured evidence strengthens the final deliverable.',
            ];
        }

        $phases = array_slice($phases, 0, $maxTasks);
        if (count($phases) < $minTasks) {
            $phases[] = [
                'title' => 'Final risk review',
                'description' => 'Identify remaining delivery risks and apply a final mitigation checklist.',
                'priority' => 'Haute',
                'reason' => 'Last-mile risk control improves on-time completion.',
            ];
            $phases = array_slice($phases, 0, $maxTasks);
        }

        $items = [];
        $total = max(1, count($phases));
        foreach ($phases as $index => $phase) {
            $phaseStartOffset = (int) floor(($spanDays * $index) / $total);
            $phaseEndOffset = (int) floor(($spanDays * ($index + 1)) / $total);
            $phaseStart = $this->clampDate($start->modify(sprintf('+%d day', $phaseStartOffset))->format('Y-m-d'), $start, $end);
            $phaseDue = $this->clampDate($start->modify(sprintf('+%d day', max($phaseStartOffset, $phaseEndOffset)))->format('Y-m-d'), $start, $end);
            if ($phaseDue < $phaseStart) {
                $phaseDue = $phaseStart;
            }

            $items[] = [
                'title' => (string) ($phase['title'] ?? 'Project task'),
                'description' => (string) ($phase['description'] ?? 'Execute this project task.'),
                'priority' => (string) ($phase['priority'] ?? 'Moyenne'),
                'status' => 'A faire',
                'start_date' => $phaseStart->format('Y-m-d'),
                'due_date' => $phaseDue->format('Y-m-d'),
                'reason' => (string) ($phase['reason'] ?? 'Improves delivery reliability.'),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) ($a['start_date'] ?? ''), (string) ($b['start_date'] ?? '')));

        return $this->applyTeamRoleDistribution(array_slice($items, 0, $maxTasks), $teamProfiles);
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @param array<int,array<string,mixed>> $teamProfiles
     * @return array<int,array<string,mixed>>
     */
    private function applyTeamRoleDistribution(array $items, array $teamProfiles): array
    {
        if ($items === []) {
            return [];
        }
        if ($teamProfiles === []) {
            return $items;
        }

        $byRole = [];
        foreach ($teamProfiles as $profile) {
            if (!is_array($profile)) {
                continue;
            }
            $role = mb_strtolower(trim((string) ($profile['project_role'] ?? 'owner')));
            if (!isset($byRole[$role])) {
                $byRole[$role] = [];
            }
            $byRole[$role][] = $profile;
        }

        $roleCursor = [];
        $fallbackRoleOrder = ['owner', 'dev', 'designer', 'analyst', 'qa'];
        $assignByExpectedRole = function (string $expectedRole) use (&$byRole, &$roleCursor, $fallbackRoleOrder): array {
            $expected = mb_strtolower(trim($expectedRole));
            $candidateRole = $expected !== '' && isset($byRole[$expected]) && $byRole[$expected] !== [] ? $expected : '';
            if ($candidateRole === '') {
                foreach ($fallbackRoleOrder as $role) {
                    if (isset($byRole[$role]) && $byRole[$role] !== []) {
                        $candidateRole = $role;
                        break;
                    }
                }
            }
            if ($candidateRole === '') {
                foreach ($byRole as $role => $profiles) {
                    if ($profiles !== []) {
                        $candidateRole = (string) $role;
                        break;
                    }
                }
            }
            if ($candidateRole === '') {
                return [];
            }

            $cursor = (int) ($roleCursor[$candidateRole] ?? 0);
            $list = $byRole[$candidateRole];
            $selected = $list[$cursor % count($list)];
            $roleCursor[$candidateRole] = $cursor + 1;
            return is_array($selected) ? $selected : [];
        };

        foreach ($items as &$item) {
            if (!is_array($item)) {
                continue;
            }
            $title = mb_strtolower(trim((string) ($item['title'] ?? '')));
            $description = mb_strtolower(trim((string) ($item['description'] ?? '')));
            $expectedRole = (string) ($item['assignee_role'] ?? '');
            if ($expectedRole === '') {
                if (preg_match('/design|ux|ui|wireframe|prototype/', $title . ' ' . $description)) {
                    $expectedRole = 'designer';
                } elseif (preg_match('/communication|branding|social|campaign|poster|content/', $title . ' ' . $description)) {
                    $expectedRole = 'designer';
                } elseif (preg_match('/data|analysis|model|report|insight/', $title . ' ' . $description)) {
                    $expectedRole = 'analyst';
                } elseif (preg_match('/budget|cost|forecast|risk|kpi|metric|finance/', $title . ' ' . $description)) {
                    $expectedRole = 'analyst';
                } elseif (preg_match('/test|qa|validation|bug/', $title . ' ' . $description)) {
                    $expectedRole = 'qa';
                } elseif (preg_match('/build|implement|api|integration|backend|frontend|dev/', $title . ' ' . $description)) {
                    $expectedRole = 'dev';
                } else {
                    $expectedRole = 'owner';
                }
            }

            $selected = $assignByExpectedRole($expectedRole);
            if ($selected !== []) {
                $item['assignee_role'] = (string) ($selected['project_role'] ?? $expectedRole);
                $item['assignee_email'] = (string) ($selected['email'] ?? '');
                $item['assignee_name'] = (string) ($selected['name'] ?? '');
            } else {
                $item['assignee_role'] = $expectedRole;
                $item['assignee_email'] = (string) ($item['assignee_email'] ?? '');
                $item['assignee_name'] = (string) ($item['assignee_name'] ?? '');
            }
        }
        unset($item);

        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $teamProfiles
     * @return array<int,array<string,mixed>>
     */
    private function normalizeTeamProfiles(array $teamProfiles, int $teamSize, Project $project): array
    {
        $signals = $this->inferProjectSignals($project);
        $defaultRoleCycle = ['owner', 'dev', 'designer', 'analyst', 'qa'];
        if (in_array('development', (array) ($signals['domains'] ?? []), true)) {
            $defaultRoleCycle = ['owner', 'dev', 'dev', 'qa', 'analyst', 'designer'];
        } elseif (in_array('design', (array) ($signals['domains'] ?? []), true)) {
            $defaultRoleCycle = ['owner', 'designer', 'designer', 'dev', 'qa', 'analyst'];
        } elseif (in_array('data-analysis', (array) ($signals['domains'] ?? []), true)) {
            $defaultRoleCycle = ['owner', 'analyst', 'analyst', 'dev', 'qa', 'designer'];
        } elseif (in_array('event', (array) ($signals['domains'] ?? []), true) || in_array('business', (array) ($signals['domains'] ?? []), true)) {
            $defaultRoleCycle = ['owner', 'owner', 'analyst', 'designer', 'qa', 'dev'];
        } elseif (in_array('education', (array) ($signals['domains'] ?? []), true) || in_array('content', (array) ($signals['domains'] ?? []), true)) {
            $defaultRoleCycle = ['owner', 'analyst', 'designer', 'owner', 'qa', 'dev'];
        }

        $normalized = [];
        $idx = 0;
        foreach ($teamProfiles as $profile) {
            if (!is_array($profile)) {
                continue;
            }
            $email = trim((string) ($profile['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $name = trim((string) ($profile['name'] ?? $email));
            $projectRole = trim((string) ($profile['project_role'] ?? ''));
            if ($projectRole === '') {
                $projectRole = $defaultRoleCycle[$idx % count($defaultRoleCycle)];
            }
            $normalized[] = [
                'email' => $email,
                'name' => $name,
                'project_role' => mb_strtolower($projectRole),
            ];
            $idx++;
        }

        if ($normalized === []) {
            $normalized[] = [
                'email' => 'owner@local',
                'name' => 'Project Owner',
                'project_role' => 'owner',
            ];
        }

        while (count($normalized) < max(1, $teamSize)) {
            $i = count($normalized);
            $role = $defaultRoleCycle[$i % count($defaultRoleCycle)];
            $normalized[] = [
                'email' => sprintf('member%d@local', $i + 1),
                'name' => sprintf('Member %d', $i + 1),
                'project_role' => $role,
            ];
        }

        return $normalized;
    }

    /**
     * @return array{domains:array<int,string>,complexity:string,concept:string,outputs:array<int,string>}
     */
    private function inferProjectSignals(Project $project): array
    {
        $title = mb_strtolower((string) ($project->getTitre() ?? ''));
        $description = mb_strtolower((string) ($project->getDescription() ?? ''));
        $text = $title . ' ' . $description;

        $domains = [];
        $domainKeywords = [
            'development' => ['app', 'api', 'backend', 'frontend', 'code', 'dev', 'mobile', 'web', 'integration', 'system'],
            'data-analysis' => ['data', 'dataset', 'analysis', 'analytics', 'ml', 'ai', 'model', 'prediction', 'statistic'],
            'research' => ['research', 'study', 'literature', 'paper', 'report', 'thesis', 'memoire'],
            'design' => ['design', 'ui', 'ux', 'prototype', 'wireframe', 'figma', 'branding'],
            'event' => ['event', 'campaign', 'workshop', 'conference', 'launch', 'marketing', 'forum', 'evenement', 'événement', 'organisation', 'organiser', 'speaker', 'sponsor', 'inscription', 'agenda'],
            'business' => ['business', 'strategy', 'market', 'client', 'sales', 'pricing', 'revenue', 'go-to-market'],
            'operations' => ['operations', 'process', 'workflow', 'logistics', 'inventory', 'supply', 'sop'],
            'education' => ['course', 'student', 'training', 'learning', 'curriculum', 'exam', 'classroom'],
            'content' => ['content', 'video', 'podcast', 'editorial', 'newsletter', 'script', 'publication'],
            'finance' => ['budget', 'cost', 'finance', 'financial', 'forecast', 'cashflow'],
            'health' => ['health', 'clinic', 'patient', 'medical', 'wellbeing', 'wellness'],
            'legal' => ['legal', 'compliance', 'regulation', 'contract', 'policy', 'gdpr'],
        ];

        foreach ($domainKeywords as $domain => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $domains[] = $domain;
                    break;
                }
            }
        }
        if ($domains === []) {
            $domains[] = 'general';
        }

        $complexityScore = 0;
        $complexityScore += mb_strlen($description) >= 220 ? 2 : (mb_strlen($description) >= 80 ? 1 : 0);
        $complexityScore += count($domains) >= 3 ? 2 : (count($domains) >= 2 ? 1 : 0);
        $complexityScore += str_contains($text, 'integration') || str_contains($text, 'architecture') ? 1 : 0;
        $complexityScore += str_contains($text, 'real-time') || str_contains($text, 'security') ? 1 : 0;

        $complexity = $complexityScore >= 5 ? 'high' : ($complexityScore >= 3 ? 'medium' : 'low');
        $concept = $this->extractProjectConcept($project);
        $outputs = $this->extractExpectedOutputs($project, array_values(array_unique($domains)));

        return [
            'domains' => array_values(array_unique($domains)),
            'complexity' => $complexity,
            'concept' => $concept,
            'outputs' => $outputs,
        ];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function getSuggestedTaskRange(Project $project, int $teamSize, string $complexity): array
    {
        $start = \DateTimeImmutable::createFromInterface($project->getDateDebut() ?? new \DateTimeImmutable('today'));
        $end = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $start);
        $spanDays = max(1, (int) $start->diff($end)->days);

        $min = $teamSize <= 1 ? 4 : ($teamSize <= 3 ? 5 : 6);
        $max = $teamSize <= 1 ? 6 : ($teamSize <= 3 ? 8 : 10);

        if ($complexity === 'high') {
            $min += 1;
            $max += 1;
        } elseif ($complexity === 'low') {
            $max -= 1;
        }

        if ($spanDays <= 5) {
            $min = max(3, $min - 1);
            $max = max($min + 1, $max - 2);
        } elseif ($spanDays >= 25) {
            $max += 1;
        }

        return [max(3, $min), min(12, max($max, $min + 1))];
    }

    private function resolveProjectFocusLabel(array $domains): string
    {
        $primary = $this->resolvePrimaryDomain($domains);
        if ($primary === 'development') {
            return 'Technical';
        }
        if ($primary === 'data-analysis') {
            return 'Analytical';
        }
        if ($primary === 'design') {
            return 'Design';
        }
        if ($primary === 'research') {
            return 'Research';
        }
        if ($primary === 'event') {
            return 'Delivery';
        }
        if ($primary === 'business') {
            return 'Business';
        }
        if ($primary === 'operations') {
            return 'Operational';
        }
        if ($primary === 'education') {
            return 'Learning';
        }
        if ($primary === 'content') {
            return 'Editorial';
        }
        if ($primary === 'finance') {
            return 'Financial';
        }
        if ($primary === 'health') {
            return 'Clinical';
        }
        if ($primary === 'legal') {
            return 'Compliance';
        }

        return 'Project';
    }

    /**
     * @param array<int,string> $domains
     * @return array<int,array{item:string,priority:string,reason:string}>
     */
    private function buildDomainSpecificQaChecks(array $domains, string $concept): array
    {
        $checks = [];
        $primary = $this->resolvePrimaryDomain($domains);
        if (in_array('development', $domains, true)) {
            $checks[] = [
                'item' => 'API contracts and integration points verified end-to-end.',
                'priority' => 'Haute',
                'reason' => 'Prevents runtime integration regressions.',
            ];
        }
        if (in_array('data-analysis', $domains, true)) {
            $checks[] = [
                'item' => sprintf('Dataset assumptions validated and traceable for %s.', $concept),
                'priority' => 'Haute',
                'reason' => 'Prevents invalid conclusions from dirty data.',
            ];
        }
        if (in_array('design', $domains, true)) {
            $checks[] = [
                'item' => 'Design consistency and accessibility pass completed.',
                'priority' => 'Moyenne',
                'reason' => 'Improves usability and delivery quality.',
            ];
        }
        if (in_array('research', $domains, true)) {
            $checks[] = [
                'item' => 'References and citations cross-checked against claims.',
                'priority' => 'Moyenne',
                'reason' => 'Strengthens academic and analytical credibility.',
            ];
        }
        if ($primary === 'event') {
            $checks[] = [
                'item' => sprintf('Run-of-show, venue checklist, and speaker confirmations validated for %s.', $concept),
                'priority' => 'Haute',
                'reason' => 'Execution reliability depends on logistics readiness.',
            ];
        }
        if ($primary === 'business') {
            $checks[] = [
                'item' => 'Assumptions in market sizing, pricing, and sales funnel are reviewed.',
                'priority' => 'Haute',
                'reason' => 'Business recommendations depend on realistic assumptions.',
            ];
        }
        if ($primary === 'education') {
            $checks[] = [
                'item' => 'Learning outcomes, rubric, and assessment alignment are validated.',
                'priority' => 'Moyenne',
                'reason' => 'Pedagogical quality requires alignment between objectives and evaluation.',
            ];
        }
        if ($primary === 'legal') {
            $checks[] = [
                'item' => 'Compliance obligations and contract/policy wording are legally cross-checked.',
                'priority' => 'Haute',
                'reason' => 'Reduces legal and regulatory exposure.',
            ];
        }
        if ($checks === []) {
            $checks[] = [
                'item' => sprintf('Stakeholder review completed for %s output.', $concept),
                'priority' => 'Moyenne',
                'reason' => 'Improves alignment before final handoff.',
            ];
        }

        return $checks;
    }

    private function classifyAssignmentLane(Assignment $assignment): string
    {
        $text = mb_strtolower(trim((string) $assignment->getTitre() . ' ' . (string) $assignment->getDescription()));
        if (preg_match('/scope|spec|requirement|brief|plan/', $text)) {
            return 'scoping';
        }
        if (preg_match('/design|wireframe|prototype|ux|ui/', $text)) {
            return 'design';
        }
        if (preg_match('/build|implement|develop|code|integration/', $text)) {
            return 'build';
        }
        if (preg_match('/test|qa|validation|review|bug/', $text)) {
            return 'validation';
        }
        if (preg_match('/deploy|submit|publish|release|handoff/', $text)) {
            return 'delivery';
        }
        if (preg_match('/meeting|sync|coordination|align/', $text)) {
            return 'coordination';
        }
        return 'build';
    }

    /**
     * @return array{title:string,description:string}
     */
    private function contextualizeTaskContent(string $title, string $description, string $concept, array $outputs, Project $project): array
    {
        $signals = $this->inferProjectSignals($project);
        $isDataProject = in_array('data-analysis', (array) ($signals['domains'] ?? []), true);
        $normalizedTitle = trim($title);
        $normalizedDescription = trim($description);
        $genericTitlePatterns = [
            'task',
            'tache',
            'todo',
            'work on project',
            'project task',
            'implement feature',
            'do research',
        ];

        $lowTitle = mb_strtolower($normalizedTitle);
        $isGeneric = mb_strlen($normalizedTitle) < 7;
        foreach ($genericTitlePatterns as $pattern) {
            if (str_contains($lowTitle, $pattern)) {
                $isGeneric = true;
                break;
            }
        }

        if ($isGeneric) {
            $artifact = (string) ($outputs[0] ?? 'deliverable');
            $normalizedTitle = sprintf('%s for %s', ucfirst($artifact), $concept);
        }

        if (!$isDataProject) {
            $fallbackArtifact = (string) ($outputs[0] ?? 'deliverable pack');
            $normalizedTitle = (string) preg_replace('/\b(dataset|model|training|notebook)\b/i', $fallbackArtifact, $normalizedTitle);
            $normalizedDescription = (string) preg_replace('/\b(dataset|model|training|notebook)\b/i', $fallbackArtifact, $normalizedDescription);
        }

        if (mb_strlen($normalizedDescription) < 40) {
            $artifacts = implode(', ', array_slice($outputs, 0, 2));
            $normalizedDescription = sprintf(
                'Produce a concrete output for %s: %s. Include validation criteria and final handoff notes.',
                $concept,
                $artifacts !== '' ? $artifacts : 'project deliverable'
            );
        } elseif (!preg_match('/(deliverable|api|screen|report|prototype|dataset|test|module|documentation|plan|budget|agenda|campaign|contract|rubric|checklist)/i', $normalizedDescription)) {
            $artifact = (string) ($outputs[0] ?? 'deliverable');
            $normalizedDescription .= sprintf(' Final expected output: %s for %s.', $artifact, $concept);
        }

        return [
            'title' => $normalizedTitle,
            'description' => $normalizedDescription,
        ];
    }

    private function extractProjectConcept(Project $project): string
    {
        $title = trim((string) ($project->getTitre() ?? ''));
        $description = trim((string) ($project->getDescription() ?? ''));
        if ($title !== '') {
            return $title;
        }
        if ($description === '') {
            return 'project delivery';
        }

        $firstSentence = preg_split('/[.!?\n]/', $description)[0] ?? '';
        $firstSentence = trim((string) $firstSentence);
        if ($firstSentence !== '') {
            return mb_substr($firstSentence, 0, 80);
        }

        return mb_substr($description, 0, 80);
    }

    /**
     * @param array<int,string> $domains
     * @return array<int,string>
     */
    private function extractExpectedOutputs(Project $project, array $domains): array
    {
        $text = mb_strtolower((string) ($project->getTitre() ?? '') . ' ' . (string) ($project->getDescription() ?? ''));
        $outputs = [];

        $lexicon = [
            'api' => 'API endpoint specification',
            'dashboard' => 'dashboard screen',
            'mobile' => 'mobile user flow',
            'prototype' => 'interactive prototype',
            'report' => 'structured report',
            'dataset' => 'clean dataset',
            'model' => 'trained model',
            'presentation' => 'presentation deck',
            'test' => 'test plan',
            'documentation' => 'technical documentation',
        ];
        foreach ($lexicon as $needle => $output) {
            if (str_contains($text, $needle)) {
                $outputs[] = $output;
            }
        }

        if ($outputs === []) {
            $primary = $this->resolvePrimaryDomain($domains);
            if ($primary === 'development') {
                $outputs = ['implemented module', 'integration test report', 'technical documentation'];
            } elseif ($primary === 'data-analysis') {
                $outputs = ['validated dataset', 'analysis notebook/report', 'insight summary'];
            } elseif ($primary === 'design') {
                $outputs = ['wireframe set', 'interactive prototype', 'design guidelines'];
            } elseif ($primary === 'research') {
                $outputs = ['literature review', 'methodology section', 'final report'];
            } elseif ($primary === 'event') {
                $outputs = ['event plan', 'run-of-show document', 'post-event report'];
            } elseif ($primary === 'business') {
                $outputs = ['market analysis brief', 'pricing strategy', 'go-to-market plan'];
            } elseif ($primary === 'operations') {
                $outputs = ['process map', 'SOP checklist', 'operations KPI dashboard'];
            } elseif ($primary === 'education') {
                $outputs = ['course outline', 'assessment rubric', 'teaching material pack'];
            } elseif ($primary === 'content') {
                $outputs = ['content calendar', 'production script', 'distribution plan'];
            } elseif ($primary === 'finance') {
                $outputs = ['budget forecast', 'cashflow projection', 'financial risk note'];
            } elseif ($primary === 'health') {
                $outputs = ['care protocol draft', 'patient education sheet', 'outcome tracking report'];
            } elseif ($primary === 'legal') {
                $outputs = ['compliance matrix', 'policy draft', 'contract checklist'];
            } else {
                $outputs = ['project plan', 'core deliverable', 'final validation checklist'];
            }
        }

        return array_values(array_unique(array_slice($outputs, 0, 4)));
    }

    /**
     * @param array<int,array<string,string>> $phase
     * @return array<int,array{title:string,description:string,priority:string,reason:string}>
     */
    private function buildDomainExecutionPhases(string $primaryDomain, string $concept, array $outputs, string $projectFocus): array
    {
        $mainOutput = (string) ($outputs[0] ?? 'deliverable');

        return match ($primaryDomain) {
            'event' => [
                [
                    'title' => sprintf('Program and speaker planning for %s', $concept),
                    'description' => sprintf('Define agenda, speaker shortlist, session format, and expected outputs (%s).', $mainOutput),
                    'priority' => 'Haute',
                    'reason' => 'Program clarity drives event value and attendance.',
                ],
                [
                    'title' => 'Logistics and venue readiness',
                    'description' => 'Confirm venue, equipment, registration flow, volunteer roles, and contingency plan.',
                    'priority' => 'Haute',
                    'reason' => 'Operational readiness prevents day-of-event failures.',
                ],
                [
                    'title' => 'Communication and sponsorship rollout',
                    'description' => 'Launch communication assets, outreach timeline, sponsor follow-ups, and attendee reminders.',
                    'priority' => 'Moyenne',
                    'reason' => 'Promotion and partner alignment determine participation quality.',
                ],
                [
                    'title' => sprintf('Final rehearsal and post-event report for %s', $concept),
                    'description' => 'Run a dry rehearsal, execute the run-of-show, and produce feedback + lessons report.',
                    'priority' => 'Moyenne',
                    'reason' => 'Rehearsal and retrospective improve delivery quality and future iterations.',
                ],
            ],
            'business' => [
                [
                    'title' => sprintf('%s baseline and market mapping', $projectFocus),
                    'description' => sprintf('Define customer segments, competitor map, and baseline assumptions for %s.', $concept),
                    'priority' => 'Haute',
                    'reason' => 'Reliable market framing prevents strategy drift.',
                ],
                [
                    'title' => sprintf('Build strategy package for %s', $concept),
                    'description' => 'Develop value proposition, pricing logic, and go-to-market actions with KPIs.',
                    'priority' => 'Haute',
                    'reason' => 'Core strategic package is the project backbone.',
                ],
                [
                    'title' => 'Financial viability and risk checks',
                    'description' => 'Validate budget, unit economics, forecast assumptions, and mitigation actions.',
                    'priority' => 'Moyenne',
                    'reason' => 'Financial consistency secures decision credibility.',
                ],
            ],
            'education' => [
                [
                    'title' => sprintf('Curriculum blueprint for %s', $concept),
                    'description' => 'Define learning outcomes, sequence modules, and align instructional approach.',
                    'priority' => 'Haute',
                    'reason' => 'Outcome-first design ensures coherent learning progression.',
                ],
                [
                    'title' => 'Build learning materials and assessments',
                    'description' => 'Prepare lesson content, practical activities, and evaluation rubric.',
                    'priority' => 'Haute',
                    'reason' => 'Quality content and assessment are core deliverables.',
                ],
                [
                    'title' => 'Pilot session and pedagogical adjustments',
                    'description' => 'Run pilot with sample learners, collect feedback, and adjust weak modules.',
                    'priority' => 'Moyenne',
                    'reason' => 'Pilot feedback improves real-world teaching impact.',
                ],
            ],
            'operations' => [
                [
                    'title' => sprintf('%s process baseline and bottleneck mapping', $projectFocus),
                    'description' => 'Document current workflow, cycle-time bottlenecks, and ownership matrix.',
                    'priority' => 'Haute',
                    'reason' => 'Clear process visibility is needed before optimization.',
                ],
                [
                    'title' => sprintf('Implement optimized workflow for %s', $concept),
                    'description' => 'Deploy revised process steps, SOP draft, and escalation flow.',
                    'priority' => 'Haute',
                    'reason' => 'Process redesign delivers measurable efficiency gains.',
                ],
                [
                    'title' => 'Operational KPI monitoring and stabilization',
                    'description' => 'Track cycle time, quality incidents, and adjust SOP for stable adoption.',
                    'priority' => 'Moyenne',
                    'reason' => 'Sustained adoption requires KPI-based stabilization.',
                ],
            ],
            'content' => [
                [
                    'title' => sprintf('Editorial strategy for %s', $concept),
                    'description' => 'Define audience, messaging pillars, and content calendar.',
                    'priority' => 'Haute',
                    'reason' => 'Editorial clarity increases consistency and engagement.',
                ],
                [
                    'title' => 'Content production sprint',
                    'description' => 'Create scripts, visual assets, and publication-ready pieces.',
                    'priority' => 'Haute',
                    'reason' => 'Production throughput is the critical path for delivery.',
                ],
                [
                    'title' => 'Distribution and performance analytics',
                    'description' => 'Publish content, monitor engagement KPIs, and refine channel strategy.',
                    'priority' => 'Moyenne',
                    'reason' => 'Distribution quality determines outcome reach.',
                ],
            ],
            default => [
                [
                    'title' => sprintf('%s baseline and resource mapping', $projectFocus),
                    'description' => sprintf('Collect references and produce the initial %s blueprint for %s.', $mainOutput, $concept),
                    'priority' => 'Moyenne',
                    'reason' => 'A strong baseline reduces execution uncertainty.',
                ],
                [
                    'title' => sprintf('Build core %s for %s', $mainOutput, $concept),
                    'description' => sprintf('Implement the central value and ship a first usable version of the %s.', $mainOutput),
                    'priority' => 'Haute',
                    'reason' => 'Core implementation is the critical path to completion.',
                ],
                [
                    'title' => sprintf('QA and final packaging of %s', $concept),
                    'description' => sprintf('Run quality checks, fix blockers, and finalize submission assets (%s).', implode(', ', array_slice($outputs, 0, 3))),
                    'priority' => 'Moyenne',
                    'reason' => 'Quality and packaging protect final evaluation and stability.',
                ],
            ],
        };
    }

    /**
     * @param array<int,string> $domains
     */
    private function resolvePrimaryDomain(array $domains): string
    {
        $priority = [
            'event',
            'business',
            'operations',
            'education',
            'content',
            'finance',
            'health',
            'legal',
            'development',
            'data-analysis',
            'design',
            'research',
            'general',
        ];
        foreach ($priority as $domain) {
            if (in_array($domain, $domains, true)) {
                return $domain;
            }
        }
        return 'general';
    }

    private function clampDate(string $date, \DateTimeImmutable $min, \DateTimeImmutable $max): \DateTimeImmutable
    {
        $candidate = \DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: $min;
        if ($candidate < $min) {
            return $min;
        }
        if ($candidate > $max) {
            return $max;
        }
        return $candidate;
    }

    private function isDoneStatus(?string $status): bool
    {
        $normalized = mb_strtolower(trim((string) $status));
        $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);
        return in_array($normalized, ['termine', 'completed', 'done'], true);
    }

    /**
     * @param Assignment[] $assignments
     * @return array{
     *   project_title:string,
     *   generated_at:string,
     *   executive_summary:string,
     *   achievements:array<int,string>,
     *   challenges:array<int,string>,
     *   lessons_learned:array<int,string>,
     *   next_steps:array<int,string>,
     *   metrics:array<string,mixed>
     * }
     */
    private function buildCompletionReportFallback(Project $project, array $assignments): array
    {
        $total = count($assignments);
        $done = count(array_filter($assignments, fn ($a) => $a instanceof Assignment && $this->isDoneStatus($a->getStatut())));
        $high = count(array_filter($assignments, fn ($a) => $a instanceof Assignment && $a->getPriorite() === 'Haute'));
        $completionRate = $total > 0 ? round(($done / $total) * 100, 1) : 0.0;
        $signals = $this->inferProjectSignals($project);
        $concept = (string) ($signals['concept'] ?? (string) $project->getTitre());
        $outputs = is_array($signals['outputs'] ?? null) ? $signals['outputs'] : ['deliverable'];
        $durationDays = 0;
        if ($project->getDateDebut() instanceof \DateTimeInterface && $project->getDateFin() instanceof \DateTimeInterface) {
            $durationDays = (int) \DateTimeImmutable::createFromInterface($project->getDateDebut())->diff(\DateTimeImmutable::createFromInterface($project->getDateFin()))->days;
        }

        return [
            'project_title' => (string) $project->getTitre(),
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'executive_summary' => sprintf(
                'Project "%s" reached completion with %s%% task completion across %d planned task(s) over %d day(s), delivering %s.',
                $concept,
                number_format($completionRate, 1),
                $total,
                $durationDays,
                implode(', ', array_slice($outputs, 0, 2))
            ),
            'achievements' => [
                sprintf('Completed %d out of %d planned tasks for %s.', $done, $total, $concept),
                sprintf('Handled %d high-priority task(s) within project scope.', $high),
                sprintf('Delivered concrete outputs: %s.', implode(', ', array_slice($outputs, 0, 2))),
            ],
            'challenges' => [
                sprintf('Maintaining schedule consistency for %s milestones.', $concept),
                'Balancing urgent work with quality assurance effort.',
                'Reducing context switching during critical execution phases.',
            ],
            'lessons_learned' => [
                'Define acceptance criteria early to reduce rework.',
                'Prioritize critical-path tasks first when deadlines tighten.',
                sprintf('Keep weekly checkpoints to protect %s quality before delivery.', $outputs[0] ?? 'output'),
            ],
            'next_steps' => [
                sprintf('Archive key deliverables and documentation for %s in one shared location.', $concept),
                'Run a short retrospective and convert lessons into checklist items.',
                'Create a starter template for the next similar project.',
            ],
            'metrics' => [
                'total_tasks' => $total,
                'completed_tasks' => $done,
                'completion_rate' => $completionRate,
                'project_duration_days' => $durationDays,
            ],
        ];
    }

    /**
     * @param mixed $list
     * @return array<int,string>
     */
    private function normalizeStringList(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $result = [];
        foreach (array_slice($list, 0, 8) as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $result[] = $text;
            }
        }

        return $result;
    }
}
