<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Matiere;

class StatisticsService
{
    public function __construct(
        private StreakService $streakService
    ) {}

    public function calculateOverallAverage(User $user): float
    {
        $evaluations = $user->getEvaluations();
        if ($evaluations->isEmpty()) return 0;

        $total = 0;
        foreach ($evaluations as $eval) {
            $total += $eval->getPercentage();
        }

        return round($total / $evaluations->count(), 2);
    }

    public function predictNextScore(User $user, ?Matiere $matiere = null): array
    {
        $evaluations = $this->streakService->getEvaluationsOrderedByDate($user, $matiere);

        if ($evaluations->count() < 3) {
            return [
                'prediction' => null,
                'confidence' => 0,
                'trend'      => 'insufficient_data',
                'message'    => 'Need at least 3 evaluations for prediction',
                'slope'      => 0,
            ];
        }

        $recent = array_slice($evaluations->toArray(), -10);
        $scores = array_map(fn($e) => $e->getPercentage(), $recent);
        $n = count($scores);

        [$slope, $intercept] = $this->linearRegression($scores);

        $prediction = max(0, min(100, $slope * ($n + 1) + $intercept));

        $trend = 'stable';
        $trendEmoji = '➡️';
        $message = 'Your performance is stable';

        if ($slope > 2)  { $trend = 'improving'; $trendEmoji = '📈'; $message = 'Great! Your scores are improving'; }
        elseif ($slope < -2) { $trend = 'declining'; $trendEmoji = '📉'; $message = 'Warning: Your scores are declining'; }

        $mean   = array_sum($scores) / $n;
        $stdDev = sqrt(array_sum(array_map(fn($s) => pow($s - $mean, 2), $scores)) / $n);

        $confidence = round((min(100, $n * 10) + max(0, 100 - $stdDev * 2)) / 2, 1);

        return [
            'prediction'         => round($prediction, 1),
            'confidence'         => $confidence,
            'trend'              => $trend,
            'trend_emoji'        => $trendEmoji,
            'message'            => $message,
            'slope'              => round($slope, 2),
            'data_points'        => $n,
            'current_average'    => round($mean, 2),
            'standard_deviation' => round($stdDev, 2),
        ];
    }

    public function getPerformanceByPeriod(User $user, string $period = 'month'): array
    {
        $evaluations = $this->streakService->getEvaluationsOrderedByDate($user);
        $stats = [];

        foreach ($evaluations as $eval) {
            $date = $eval->getDateEvaluation();
            [$key, $label] = match($period) {
                'week'  => [$date->format('Y-W'), 'Week ' . $date->format('W, Y')],
                'year'  => [$date->format('Y'),   $date->format('Y')],
                default => [$date->format('Y-m'),  $date->format('F Y')],
            };

            $stats[$key] ??= ['label' => $label, 'total' => 0, 'count' => 0, 'success' => 0, 'perfect' => 0, 'evaluations' => []];

            $pct = $eval->getPercentage();
            $stats[$key]['total']          += $pct;
            $stats[$key]['count']          ++;
            $stats[$key]['evaluations'][]  = $pct;
            if ($pct >= 75) $stats[$key]['success']++;
            if ($pct >= 90) $stats[$key]['perfect']++;
        }

        foreach ($stats as $key => $data) {
            $stats[$key]['average']       = round($data['total'] / $data['count'], 2);
            $stats[$key]['success_rate']  = round($data['success'] / $data['count'] * 100, 1);
            $stats[$key]['perfect_rate']  = round($data['perfect'] / $data['count'] * 100, 1);
            $stats[$key]['min']           = min($data['evaluations']);
            $stats[$key]['max']           = max($data['evaluations']);
        }

        return $stats;
    }

    public function getCompleteStatistics(User $user): array
{
    $evaluations = $user->getEvaluations();

    // Compter les bonnes et parfaites notes
    $perfectCount   = 0;
    $highScoreCount = 0;

    foreach ($evaluations as $eval) {
        $pct = $eval->getPercentage();
        if ($pct >= 90) $perfectCount++;
        if ($pct >= 75) $highScoreCount++;
    }

    return [
        'overall' => [
            'total_evaluations' => $evaluations->count(),
            'average'           => $this->calculateOverallAverage($user),
            // ✅ Les 2 clés manquantes !
            'perfect_count'     => $perfectCount,
            'high_score_count'  => $highScoreCount,
        ],
        'by_month'        => $this->getPerformanceByPeriod($user, 'month'),
        'by_week'         => $this->getPerformanceByPeriod($user, 'week'),
        'history_30_days' => $this->getStreakHistory($user, 30),
        'prediction'      => $this->predictNextScore($user),
    ];
}

    // ==========================================
    // Privé
    // ==========================================

    private function linearRegression(array $scores): array
    {
        $n = count($scores);
        $sumX = $sumY = $sumXY = $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $sumX  += $x;
            $sumY  += $scores[$i];
            $sumXY += $x * $scores[$i];
            $sumX2 += $x * $x;
        }

        $slope     = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX ** 2);
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [$slope, $intercept];
    }

    public function getStreakHistory(User $user, int $days = 30): array
{
    $history      = [];
    $evaluations  = $this->streakService->getEvaluationsOrderedByDate($user);
    $currentStreak = 0;
    $startDate    = new \DateTime("-{$days} days");

    foreach ($evaluations as $eval) {
        if ($eval->getDateEvaluation() >= $startDate) {
            $percentage = $eval->getPercentage();

            if ($percentage >= 75) {
                $currentStreak++;
            } else {
                $currentStreak = 0;
            }

            $history[] = [
                'date'       => $eval->getDateEvaluation()->format('Y-m-d'),
                'streak'     => $currentStreak,
                'percentage' => round($percentage, 2),
                'score'      => $eval->getScoreEval(),
                'max'        => $eval->getNoteMaximaleEval(),
            ];
        }
    }

    return $history;
}
}