<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Matiere;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class StreakService
{
    public function getHighScoreStreak(User $user, ?Matiere $matiere = null): array
    {
        $evaluations = $this->getEvaluationsOrderedByDate($user, $matiere);

        $currentStreak = 0;
        $longestStreak = 0;

        foreach ($evaluations as $eval) {
            if ($eval->getPercentage() >= 75) {
                $currentStreak++;
                $longestStreak = max($longestStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        $badges = [];
        if ($currentStreak >= 10) $badges[] = '👑 Legend';
        elseif ($currentStreak >= 7) $badges[] = '🔥 On Fire';
        elseif ($currentStreak >= 5) $badges[] = '⚡ Unstoppable';
        elseif ($currentStreak >= 3) $badges[] = '⭐ Great Streak';

        return [
            'current' => $currentStreak,
            'longest' => $longestStreak,
            'badges' => $badges,
            'status' => $this->getStreakStatus($currentStreak),
        ];
    }

    public function getPerfectScoreStreak(User $user, ?Matiere $matiere = null): array
    {
        $evaluations = $this->getEvaluationsOrderedByDate($user, $matiere);

        $currentStreak = 0;
        $longestStreak = 0;

        foreach ($evaluations as $eval) {
            if ($eval->getPercentage() >= 90) {
                $currentStreak++;
                $longestStreak = max($longestStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        $badges = [];
        if ($currentStreak >= 5) $badges[] = '🌟 Master';
        elseif ($currentStreak >= 3) $badges[] = '💎 Perfectionist';
        elseif ($currentStreak >= 2) $badges[] = '✨ Excellent';

        return [
            'current' => $currentStreak,
            'longest' => $longestStreak,
            'badges' => $badges,
            'status' => $this->getStreakStatus($currentStreak),
        ];
    }

    public function getProgressionStreak(User $user, ?Matiere $matiere = null): array
    {
        $evaluations = $this->getEvaluationsOrderedByDate($user, $matiere);

        $currentStreak = 0;
        $longestStreak = 0;
        $previousPercentage = null;

        foreach ($evaluations as $eval) {
            if ($previousPercentage !== null) {
                if ($eval->getPercentage() > $previousPercentage) {
                    $currentStreak++;
                    $longestStreak = max($longestStreak, $currentStreak);
                } else {
                    $currentStreak = 0;
                }
            }
            $previousPercentage = $eval->getPercentage();
        }

        $badges = [];
        if ($currentStreak >= 7) $badges[] = '🎖️ Excellence Path';
        elseif ($currentStreak >= 5) $badges[] = '🚀 Momentum';
        elseif ($currentStreak >= 3) $badges[] = '📈 Rising Star';

        return [
            'current' => $currentStreak,
            'longest' => $longestStreak,
            'badges' => $badges,
            'status' => $this->getStreakStatus($currentStreak),
        ];
    }

    public function getAllStreakStats(User $user, ?Matiere $matiere = null): array
    {
        return [
            'high_score'     => $this->getHighScoreStreak($user, $matiere),
            'perfect_score'  => $this->getPerfectScoreStreak($user, $matiere),
            'progression'    => $this->getProgressionStreak($user, $matiere),
        ];
    }

    public function getAllBadges(User $user): array
    {
        $allStats = $this->getAllStreakStats($user);
        $allBadges = [];

        foreach ($allStats as $type => $stats) {
            foreach ($stats['badges'] as $badge) {
                $allBadges[] = [
                    'name'   => $badge,
                    'type'   => $type,
                    'streak' => $stats['current'],
                ];
            }
        }

        return $allBadges;
    }

    // ==========================================
    // Méthodes privées utilitaires
    // ==========================================

    public function getEvaluationsOrderedByDate(User $user, ?Matiere $matiere = null): Collection
    {
        $evaluations = $user->getEvaluations()->toArray();

        if ($matiere !== null) {
            $evaluations = array_filter($evaluations, function ($eval) use ($matiere) {
                foreach ($eval->getEvalMats() as $evalMat) {
                    if ($evalMat->getMatiere() === $matiere) return true;
                }
                return false;
            });
        }

        usort($evaluations, fn($a, $b) => $a->getDateEvaluation() <=> $b->getDateEvaluation());

        return new ArrayCollection(array_values($evaluations));
    }

    public function getStreakStatus(int $streak): array
    {
        if ($streak >= 10) return ['emoji' => '👑', 'message' => 'LEGENDARY!',    'color' => 'gold',     'class' => 'warning'];
        if ($streak >= 7)  return ['emoji' => '🔥', 'message' => 'ON FIRE!',      'color' => 'red',      'class' => 'danger'];
        if ($streak >= 5)  return ['emoji' => '⚡', 'message' => 'Unstoppable!',  'color' => 'orange',   'class' => 'warning'];
        if ($streak >= 3)  return ['emoji' => '⭐', 'message' => 'Great!',         'color' => 'blue',     'class' => 'primary'];
        if ($streak >= 1)  return ['emoji' => '✨', 'message' => 'Keep Going!',   'color' => 'lightblue','class' => 'info'];

        return ['emoji' => '💤', 'message' => 'Start Now!', 'color' => 'gray', 'class' => 'secondary'];
    }

    public function getStreaksByMatiere(User $user): array
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
            'high_score'    => $this->getHighScoreStreak($user, $matiere),
            'perfect_score' => $this->getPerfectScoreStreak($user, $matiere),
            'progression'   => $this->getProgressionStreak($user, $matiere),
        ];
    }

    return $result;
}
}