<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Matiere;

class StudyCoachService
{
    public function __construct(
        private StreakService      $streakService,
        private StatisticsService  $statisticsService,
    ) {}

    public function getAIRecommendations(User $user): array
    {
        return [
            'priority_level'           => $this->calculateOverallPriorityLevel($user),
            'at_risk_subjects'         => $this->identifyAtRiskSubjects($user),
            'improvement_opportunities'=> $this->identifyImprovementOpportunities($user),
            'study_schedule'           => $this->generateOptimalStudySchedule($user),
            'learning_strategies'      => $this->recommendLearningStrategies($user),
            'smart_goals'              => $this->generateSmartGoals($user),
            'motivational_message'     => $this->generateMotivationalMessage($user),
        ];
    }

    private function identifyAtRiskSubjects(User $user): array
    {
        $atRisk = [];
        $streaksByMatiere = $this->getStreaksByMatiere($user);

        foreach ($streaksByMatiere as $data) {
            $matiere     = $data['matiere'];
            $recentEvals = $this->getRecentEvaluations($user, $matiere, 3);

            if (empty($recentEvals)) continue;

            $recentAverage = array_sum(array_map(fn($e) => $e->getPercentage(), $recentEvals)) / count($recentEvals);
            $trend         = $this->calculateTrend($recentEvals);
            $riskScore     = 0;

            if ($recentAverage < 60)  $riskScore += 40;
            elseif ($recentAverage < 75) $riskScore += 20;

            if ($trend < -2) $riskScore += 30;
            elseif ($trend < 0) $riskScore += 15;

            if ($data['high_score']['current'] == 0 && $data['high_score']['longest'] > 0) $riskScore += 20;
            if ($this->hasUpcomingUrgentEvaluations($user, $matiere)) $riskScore += 10;

            if ($riskScore > 0) {
                $atRisk[] = [
                    'matiere'        => $matiere,
                    'risk_score'     => min(100, $riskScore),
                    'recent_average' => round($recentAverage, 1),
                    'trend'          => $trend,
                    'recommendation' => $this->generateRiskRecommendation($riskScore),
                    'urgency'        => $this->calculateUrgency($riskScore),
                ];
            }
        }

        usort($atRisk, fn($a, $b) => $b['risk_score'] - $a['risk_score']);

        return $atRisk;
    }

    private function getStreaksByMatiere(User $user): array
    {
        $matieres = [];

        foreach ($user->getEvaluations() as $eval) {
            foreach ($eval->getEvalMats() as $evalMat) {
                $matiere = $evalMat->getMatiere();
                if ($matiere && !in_array($matiere, $matieres, true)) {
                    $matieres[] = $matiere;
                }
            }
        }

        $result = [];
        foreach ($matieres as $matiere) {
            $result[$matiere->getId()] = [
                'matiere'       => $matiere,
                'high_score'    => $this->streakService->getHighScoreStreak($user, $matiere),
                'perfect_score' => $this->streakService->getPerfectScoreStreak($user, $matiere),
                'progression'   => $this->streakService->getProgressionStreak($user, $matiere),
            ];
        }

        return $result;
    }

    private function getRecentEvaluations(User $user, Matiere $matiere, int $count): array
    {
        $evaluations = [];

        foreach ($user->getEvaluations() as $eval) {
            foreach ($eval->getEvalMats() as $evalMat) {
                if ($evalMat->getMatiere() === $matiere) {
                    $evaluations[] = $eval;
                    break;
                }
            }
        }

        usort($evaluations, fn($a, $b) => $b->getDateEvaluation() <=> $a->getDateEvaluation());

        return array_slice($evaluations, 0, $count);
    }

    private function calculateTrend(array $evaluations): float
    {
        if (count($evaluations) < 2) return 0;

        $scores = array_map(fn($e) => $e->getPercentage(), $evaluations);
        $n = count($scores);
        $sumX = $sumY = $sumXY = $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumX  += $i + 1;
            $sumY  += $scores[$i];
            $sumXY += ($i + 1) * $scores[$i];
            $sumX2 += ($i + 1) ** 2;
        }

        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX ** 2);
    }

    private function generateRiskRecommendation(int $riskScore): string
    {
        return match(true) {
            $riskScore >= 70 => "🚨 URGENT: Dedicate 2-3 hours daily. Consider tutoring.",
            $riskScore >= 50 => "⚠️ HIGH PRIORITY: Schedule 1-2 hours daily review sessions.",
            $riskScore >= 30 => "⚡ ATTENTION NEEDED: Increase study time by 30 minutes daily.",
            default          => "📌 MONITOR: Keep consistent practice to maintain progress.",
        };
    }

    private function calculateUrgency(int $riskScore): string
    {
        return match(true) {
            $riskScore >= 70 => 'critical',
            $riskScore >= 50 => 'high',
            $riskScore >= 30 => 'medium',
            default          => 'low',
        };
    }

    private function hasUpcomingUrgentEvaluations(User $user, Matiere $matiere): bool
    {
        $now      = new \DateTime();
        $twoWeeks = new \DateTime('+2 weeks');

        foreach ($user->getEvaluations() as $eval) {
            if ($eval->getPrioriteE() === 'urgent') {
                $date = $eval->getDateEvaluation();
                if ($date >= $now && $date <= $twoWeeks) {
                    foreach ($eval->getEvalMats() as $evalMat) {
                        if ($evalMat->getMatiere() === $matiere) return true;
                    }
                }
            }
        }

        return false;
    }

    private function calculateOverallPriorityLevel(User $user): string
    {
        $atRisk = $this->identifyAtRiskSubjects($user);
        if (empty($atRisk)) return 'relaxed';

        $avgRisk = array_sum(array_column($atRisk, 'risk_score')) / count($atRisk);

        return match(true) {
            $avgRisk >= 70 => 'critical',
            $avgRisk >= 50 => 'high',
            $avgRisk >= 30 => 'moderate',
            default        => 'low',
        };
    }

   private function generateMotivationalMessage(User $user): array
{
    $overall = $this->statisticsService->calculateOverallAverage($user);
    $streak  = $this->streakService->getHighScoreStreak($user);
    $atRisk  = $this->identifyAtRiskSubjects($user);

    $main = match(true) {
        $overall >= 90 => "🌟 Outstanding performance! You're in the top tier!",
        $overall >= 75 => "🎯 Great work! You're maintaining strong performance!",
        $overall >= 60 => "📈 You're making progress! Keep pushing forward!",
        default        => "💪 Every expert was once a beginner. You've got this!",
    };

    $additional = [];
    if ($streak['current'] >= 5) {
        $additional[] = "🔥 Your {$streak['current']}-evaluation streak is impressive!";
    }
    if (count($atRisk) > 0) {
        $additional[] = "⚠️ Focus on " . count($atRisk) . " subject(s) that need attention.";
    }

    return [
        'main'       => $main,
        'additional' => $additional,
        // ✅ La clé manquante !
        'quote'      => $this->getMotivationalQuote($overall),
    ];
}



private function identifyImprovementOpportunities(User $user): array
{
    $opportunities = [];
    $streaksByMatiere = $this->getStreaksByMatiere($user);

    foreach ($streaksByMatiere as $data) {
        $matiere     = $data['matiere'];
        $recentEvals = $this->getRecentEvaluations($user, $matiere, 5);

        if (empty($recentEvals)) continue;

        $recentAverage = array_sum(array_map(fn($e) => $e->getPercentage(), $recentEvals)) / count($recentEvals);
        $trend         = $this->calculateTrend($recentEvals);

        // Opportunité 1 : Tendance positive
        if ($trend > 0 && $recentAverage >= 60 && $recentAverage < 90) {
            $opportunities[] = [
                'matiere'         => $matiere,
                'type'            => 'positive_momentum',
                'current_average' => round($recentAverage, 1),
                'trend'           => round($trend, 2),
                'message'         => "You're on the right track! Keep the momentum going.",
                'action'          => "Aim for {$this->calculateNextTarget($recentAverage)}% in your next evaluation.",
                'icon'            => '📈',
            ];
        }

        // Opportunité 2 : Proche du palier 75%
        if ($recentAverage >= 70 && $recentAverage < 75) {
            $opportunities[] = [
                'matiere'         => $matiere,
                'type'            => 'near_threshold',
                'current_average' => round($recentAverage, 1),
                'message'         => "You're very close to the 75% threshold!",
                'action'          => "Just " . round(75 - $recentAverage, 1) . "% more to start a new streak.",
                'icon'            => '🎯',
            ];
        }

        // Opportunité 3 : Streak en construction
        if ($data['high_score']['current'] >= 2 && $data['high_score']['current'] < 5) {
            $opportunities[] = [
                'matiere'        => $matiere,
                'type'           => 'streak_building',
                'current_streak' => $data['high_score']['current'],
                'message'        => "Building a great streak!",
                'action'         => "Keep it up to unlock the 'Unstoppable ⚡' badge.",
                'icon'           => '🔥',
            ];
        }
    }

    return $opportunities;
}

private function generateOptimalStudySchedule(User $user): array
{
    $schedule     = [];
    $atRisk       = $this->identifyAtRiskSubjects($user);
    $priorityDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $dayIndex     = 0;

    foreach ($atRisk as $risk) {
        if ($dayIndex < count($priorityDays)) {
            $schedule[] = [
                'day'              => $priorityDays[$dayIndex],
                'matiere'          => $risk['matiere'],
                'duration_minutes' => $this->calculateOptimalStudyTime($risk['risk_score']),
                'priority'         => 'high',
                'reason'           => $risk['recommendation'],
            ];
            $dayIndex++;
        }
    }

    return $schedule;
}

private function recommendLearningStrategies(User $user): array
{
    $strategies      = [];
    $overall         = $this->statisticsService->calculateOverallAverage($user);
    $evaluationCount = $user->getEvaluations()->count();

    if ($overall < 60) {
        $strategies[] = [
            'title'       => 'Back to Basics',
            'icon'        => '📚',
            'description' => 'Focus on understanding core concepts before moving forward.',
            'techniques'  => [
                'Use the Feynman Technique: Explain concepts in simple terms',
                'Create mind maps to visualize connections',
                'Practice active recall instead of passive reading',
            ],
        ];
    } elseif ($overall < 75) {
        $strategies[] = [
            'title'       => 'Consistency is Key',
            'icon'        => '⏰',
            'description' => 'Build regular study habits to maintain progress.',
            'techniques'  => [
                'Use the Pomodoro Technique (25 min focus, 5 min break)',
                'Review notes within 24 hours of each class',
                'Create a weekly study schedule',
            ],
        ];
    } else {
        $strategies[] = [
            'title'       => 'Excellence Mode',
            'icon'        => '🎯',
            'description' => 'Challenge yourself to reach mastery level.',
            'techniques'  => [
                'Teach concepts to others (study groups)',
                'Solve advanced practice problems',
                'Create your own exam questions',
            ],
        ];
    }

    if ($evaluationCount < 5) {
        $strategies[] = [
            'title'       => 'Build Data',
            'icon'        => '📊',
            'description' => 'Complete more evaluations to get better AI insights.',
            'techniques'  => [
                'Take practice tests regularly',
                'Track all your scores',
                'Build a performance baseline',
            ],
        ];
    }

    return $strategies;
}

private function generateSmartGoals(User $user): array
{
    $goals   = [];
    $overall = $this->statisticsService->calculateOverallAverage($user);
    $streak  = $this->streakService->getHighScoreStreak($user);

    // Objectif de moyenne
    $targetAverage = min(100, $overall + 10);
    $goals[] = [
        'type'       => 'average',
        'current'    => round($overall, 1),
        'target'     => round($targetAverage, 1),
        'deadline'   => '1 month',
        'specific'   => "Increase overall average from {$overall}% to {$targetAverage}%",
        'measurable' => "Track after each evaluation",
        'achievable' => "Focus on weak subjects",
        'relevant'   => "Better grades = better opportunities",
        'time_bound' => date('F d, Y', strtotime('+1 month')),
    ];

    // Objectif de streak
    $targetStreak = max(5, $streak['longest'] + 3);
    $goals[] = [
        'type'       => 'streak',
        'current'    => $streak['current'],
        'target'     => $targetStreak,
        'deadline'   => '2 weeks',
        'specific'   => "Build a {$targetStreak}-evaluation streak",
        'measurable' => "Get {$targetStreak} consecutive scores ≥75%",
        'achievable' => "Review before each evaluation",
        'relevant'   => "Consistency leads to mastery",
        'time_bound' => date('F d, Y', strtotime('+2 weeks')),
    ];

    return $goals;
}

private function calculateNextTarget(float $current): int
{
    if ($current < 75) return 75;
    if ($current < 80) return 80;
    if ($current < 85) return 85;
    if ($current < 90) return 90;
    return 95;
}

private function calculateOptimalStudyTime(int $riskScore): int
{
    if ($riskScore >= 70) return 120;
    if ($riskScore >= 50) return 90;
    if ($riskScore >= 30) return 60;
    return 45;
}

private function getMotivationalQuote(float $average): string
{
    $quotes = [
        "Success is the sum of small efforts repeated day in and day out. - Robert Collier",
        "The expert in anything was once a beginner. - Helen Hayes",
        "Education is not preparation for life; education is life itself. - John Dewey",
        "The beautiful thing about learning is that no one can take it away from you. - B.B. King",
        "Success is not final, failure is not fatal: it is the courage to continue that counts. - Winston Churchill",
    ];

    return $quotes[array_rand($quotes)];
}
}