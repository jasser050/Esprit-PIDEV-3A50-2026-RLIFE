<?php

namespace App\Service;

use App\Entity\StudentGamification;
use Doctrine\ORM\EntityManagerInterface;

class GamificationService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function addPoints(StudentGamification $g, int $amount): void
    {
        $g->addPoints($amount);
        $g->updateLevel();
    }

    public function updateStreak(StudentGamification $g): void
    {
        $today = new \DateTime('today');
        $last  = $g->getLastActivity();

        if (!$last) {
            $g->setStreak(1);
        } else {
            $diff = (int) $today->diff($last)->days;

            if ($diff === 1) {
                $g->setStreak($g->getStreak() + 1);
            } elseif ($diff > 1) {
                $g->setStreak(1);
            }
            // diff === 0 → même jour, on ne change rien
        }

        $g->setLastActivity($today);
    }

    public function checkDeckBadges(StudentGamification $g): array
    {
        $new = [];

        if ($g->getTotalDecksCompleted() >= 1 && !$g->hasBadge('Master Deck')) {
            $g->addBadge('Master Deck');
            $new[] = 'Master Deck 🎖';
        }

        return $new;
    }

    public function checkAnswerBadges(StudentGamification $g): array
    {
        $new = [];

        if ($g->getTotalCorrectAnswers() >= 100 && !$g->hasBadge('100 Bonnes Réponses')) {
            $g->addBadge('100 Bonnes Réponses');
            $new[] = '100 Bonnes Réponses 💯';
        }

        if ($g->getTotalCorrectAnswers() >= 500 && !$g->hasBadge('500 Bonnes Réponses')) {
            $g->addBadge('500 Bonnes Réponses');
            $new[] = '500 Bonnes Réponses 🚀';
        }

        if ($g->getLevel() >= 5 && !$g->hasBadge('Niveau 5')) {
            $g->addBadge('Niveau 5');
            $new[] = 'Niveau 5 ⭐';
        }

        if ($g->getLevel() >= 10 && !$g->hasBadge('Niveau 10')) {
            $g->addBadge('Niveau 10');
            $new[] = 'Niveau 10 🌟';
        }

        return $new;
    }

    public function checkStreakBadges(StudentGamification $g): array
    {
        $new = [];

        if ($g->getStreak() >= 7 && !$g->hasBadge('Streak 7 jours')) {
            $g->addBadge('Streak 7 jours');
            $new[] = 'Streak 7 jours 🔥';
        }

        return $new;
    }
}