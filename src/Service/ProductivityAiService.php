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
                'recommendations' => $this->normalizeRecommendations($recommendations, $userLevel),
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
    public function generateAssignmentsForProject(User $user, Project $project): array
    {
        $fallback = $this->buildProjectAssignmentFallback($project);

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

Return ONLY valid JSON (no markdown), as an array of 4 to 7 task objects:
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
PROMPT,
                (string) ($user->getEmail() ?? 'student'),
                (string) $project->getTitre(),
                (string) $project->getDescription(),
                $project->getDateDebut()?->format('Y-m-d') ?? (new \DateTimeImmutable('today'))->format('Y-m-d'),
                $project->getDateFin()?->format('Y-m-d') ?? (new \DateTimeImmutable('today'))->format('Y-m-d'),
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

            $normalized = $this->normalizeProjectAssignmentSuggestions($decoded, $project);
            return $normalized !== [] ? $normalized : $fallback;
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

        $mitigation = [
            [
                'title' => 'Risk triage and blocker removal',
                'description' => 'List blockers, assign owners, and remove the top blocker immediately.',
                'priority' => 'Haute',
                'status' => 'A faire',
                'start_date' => $today->format('Y-m-d'),
                'due_date' => $this->clampDate($today->modify('+1 day')->format('Y-m-d'), $today, $end)->format('Y-m-d'),
                'reason' => 'Fast blocker removal increases delivery probability.',
            ],
            [
                'title' => 'Critical path alignment',
                'description' => 'Reorder tasks to keep only critical path items active this week.',
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

        $cursor = $today > $projectStart ? $today : $projectStart;
        $adjustments = [];
        foreach ($open as $assignment) {
            $durationDays = 1;
            if ($assignment->getDateDebut() instanceof \DateTimeInterface && $assignment->getDateFin() instanceof \DateTimeInterface) {
                $durationDays = max(1, (int) \DateTimeImmutable::createFromInterface($assignment->getDateDebut())->diff(\DateTimeImmutable::createFromInterface($assignment->getDateFin()))->days);
            }

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
                    'reason' => 'Smoothed load to reduce overlap and deadline collisions.',
                ];
            }

            $cursor = $newDue->modify('+1 day');
        }

        return [
            'summary' => $adjustments === []
                ? 'Current schedule is already balanced.'
                : sprintf('Proposed %d date adjustment(s) to balance execution workload.', count($adjustments)),
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

        $openCount = count(array_filter($assignments, fn ($a) => $a instanceof Assignment && !$this->isDoneStatus($a->getStatut())));
        $checklist = [
            ['item' => 'Acceptance criteria documented and validated.', 'priority' => 'Haute', 'reason' => 'Prevents ambiguous completion.'],
            ['item' => 'Core flow tested on happy path and failure path.', 'priority' => 'Haute', 'reason' => 'Reduces production regressions.'],
            ['item' => 'Final deliverable reviewed for clarity and formatting.', 'priority' => 'Moyenne', 'reason' => 'Improves grading and stakeholder confidence.'],
        ];
        if ($openCount > 3) {
            $checklist[] = ['item' => 'Scope freeze confirmed for remaining sprint.', 'priority' => 'Haute', 'reason' => 'Avoids late-stage scope creep.'];
        }

        $qaTasks = [
            [
                'title' => 'Define acceptance checklist',
                'description' => 'Create final checklist and map each criterion to evidence.',
                'priority' => 'Haute',
                'status' => 'A faire',
                'start_date' => $qaStart->format('Y-m-d'),
                'due_date' => $qaDue->format('Y-m-d'),
                'reason' => 'Locks quality target before final delivery.',
            ],
            [
                'title' => 'Run QA validation pass',
                'description' => 'Execute manual/functional checks and log fixes before submission.',
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

        $openAssignments = array_values(array_filter($assignments, fn ($a) => $a instanceof Assignment && !$this->isDoneStatus($a->getStatut())));
        usort($openAssignments, fn (Assignment $a, Assignment $b) => ($a->getDateFin()?->getTimestamp() ?? PHP_INT_MAX) <=> ($b->getDateFin()?->getTimestamp() ?? PHP_INT_MAX));

        $focus = count($openAssignments) > 5
            ? 'Stabilize execution by finishing highest-impact tasks first.'
            : 'Close current tasks and secure delivery readiness.';

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
                'description' => 'Focused completion block for this priority item.',
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
                'title' => 'Sprint planning and next milestone',
                'description' => 'Prepare next milestone and backlog for upcoming week.',
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

            $prompt = sprintf(
                <<<PROMPT
You are a project post-mortem assistant for students.
User: %s
Project title: %s
Project description: %s
Project start: %s
Project end: %s
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
    private function normalizeRecommendations(array $recommendations, int $userLevel): array
    {
        $normalized = [];

        foreach ($recommendations as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = in_array(($item['type'] ?? ''), ['assignment', 'project'], true) ? $item['type'] : 'assignment';
            $targetId = (int) ($item['target_id'] ?? 0);
            if ($targetId <= 0) {
                continue;
            }

            $estimatedMinutes = max(20, (int) ($item['estimated_minutes'] ?? 60));
            $challengeMinutes = max(15, (int) ($item['challenge_minutes'] ?? (int) floor($estimatedMinutes * 0.72)));
            $rewardCoins = max(20, (int) ($item['reward_coins'] ?? ($challengeMinutes / 4.2 + $userLevel * 3)));

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

        usort($assignments, static function (Assignment $a, Assignment $b): int {
            $aTs = $a->getDateFin()?->getTimestamp() ?? PHP_INT_MAX;
            $bTs = $b->getDateFin()?->getTimestamp() ?? PHP_INT_MAX;
            return $aTs <=> $bTs;
        });

        foreach (array_slice($assignments, 0, 3) as $assignment) {
            $priority = mb_strtolower((string) $assignment->getPriorite());
            $base = match ($priority) {
                'haute', 'high' => 130,
                'moyenne', 'medium' => 95,
                default => 70,
            };
            $daysToDue = $this->daysToDate($assignment->getDateFin());
            $urgencyBoost = $daysToDue <= 1 ? 1.2 : ($daysToDue <= 3 ? 1.1 : 1.0);

            $estimatedMinutes = max(30, (int) round($base * $urgencyBoost * (1.08 - min(0.35, $userLevel * 0.03))));
            $challengeMinutes = max(20, (int) round($estimatedMinutes * 0.68));
            $rewardCoins = min(220, max(25, (int) round($challengeMinutes / 3.9 + $userLevel * 3)));
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
            $daysToDue = $this->daysToDate($project->getDateFin());
            $urgencyBoost = $daysToDue <= 2 ? 1.15 : 1.0;
            $estimatedMinutes = max(45, (int) round(150 * $urgencyBoost * (1.05 - min(0.3, $userLevel * 0.02))));
            $challengeMinutes = max(30, (int) round($estimatedMinutes * 0.7));
            $rewardCoins = min(250, max(40, (int) round($challengeMinutes / 3.8 + $userLevel * 4)));

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
    private function normalizeProjectAssignmentSuggestions(array $items, Project $project): array
    {
        $normalized = [];
        $start = \DateTimeImmutable::createFromInterface($project->getDateDebut() ?? new \DateTimeImmutable('today'));
        $end = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $start);

        foreach (array_slice($items, 0, 7) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
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

            $normalized[] = [
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'status' => $status,
                'start_date' => $startDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'reason' => trim((string) ($item['reason'] ?? 'This task reduces project delivery risk.')),
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int,array{title:string,description:string,priority:string,status:string,start_date:string,due_date:string,reason:string}>
     */
    private function buildProjectAssignmentFallback(Project $project): array
    {
        $start = \DateTimeImmutable::createFromInterface($project->getDateDebut() ?? new \DateTimeImmutable('today'));
        $end = \DateTimeImmutable::createFromInterface($project->getDateFin() ?? $start);
        $spanDays = max(1, (int) $start->diff($end)->days);

        $d1 = $start;
        $d2 = $start->modify(sprintf('+%d days', max(1, (int) floor($spanDays * 0.3))));
        $d3 = $start->modify(sprintf('+%d days', max(1, (int) floor($spanDays * 0.6))));
        $d4 = $end;

        return [
            [
                'title' => 'Define scope and success criteria',
                'description' => 'Clarify objectives, deliverables, and acceptance criteria for the project.',
                'priority' => 'Haute',
                'status' => 'A faire',
                'start_date' => $d1->format('Y-m-d'),
                'due_date' => $d2->format('Y-m-d'),
                'reason' => 'Clear scope prevents rework and delays later phases.',
            ],
            [
                'title' => 'Research and collect resources',
                'description' => 'Gather references, tools, and data needed for implementation.',
                'priority' => 'Moyenne',
                'status' => 'A faire',
                'start_date' => $d1->format('Y-m-d'),
                'due_date' => $d2->format('Y-m-d'),
                'reason' => 'Resource readiness removes execution blockers.',
            ],
            [
                'title' => 'Implement core deliverable',
                'description' => 'Build the main output of the project and validate against scope.',
                'priority' => 'Haute',
                'status' => 'A faire',
                'start_date' => $d2->format('Y-m-d'),
                'due_date' => $d3->format('Y-m-d'),
                'reason' => 'Core implementation is the critical path for completion.',
            ],
            [
                'title' => 'Review, polish, and submit',
                'description' => 'Test quality, fix issues, and finalize submission materials.',
                'priority' => 'Moyenne',
                'status' => 'A faire',
                'start_date' => $d3->format('Y-m-d'),
                'due_date' => $d4->format('Y-m-d'),
                'reason' => 'Final QA protects delivery quality and grading outcome.',
            ],
        ];
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
        $durationDays = 0;
        if ($project->getDateDebut() instanceof \DateTimeInterface && $project->getDateFin() instanceof \DateTimeInterface) {
            $durationDays = (int) \DateTimeImmutable::createFromInterface($project->getDateDebut())->diff(\DateTimeImmutable::createFromInterface($project->getDateFin()))->days;
        }

        return [
            'project_title' => (string) $project->getTitre(),
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'executive_summary' => sprintf(
                'Project "%s" reached completion with %s%% task completion across %d planned task(s) over %d day(s).',
                (string) $project->getTitre(),
                number_format($completionRate, 1),
                $total,
                $durationDays
            ),
            'achievements' => [
                sprintf('Completed %d out of %d planned tasks.', $done, $total),
                sprintf('Handled %d high-priority task(s) within project scope.', $high),
                'Established a reusable delivery workflow from planning to execution.',
            ],
            'challenges' => [
                'Maintaining schedule consistency across parallel tasks.',
                'Balancing urgent work with quality assurance effort.',
                'Reducing context switching during critical execution phases.',
            ],
            'lessons_learned' => [
                'Define acceptance criteria early to reduce rework.',
                'Prioritize critical-path tasks first when deadlines tighten.',
                'Keep weekly checkpoints to detect drift before it compounds.',
            ],
            'next_steps' => [
                'Archive key deliverables and documentation in one shared location.',
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
