<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\AssignmentRepository;

class AssignmentStatsService
{
    public function __construct(
        private AssignmentRepository $assignmentRepository
    ) {
    }

    public function getAssignmentStats(User $user): array
    {
        $assignments = $this->assignmentRepository->findByUser($user);
        $total = count($assignments);

        $doneStatuses = ['Termine', 'Terminé', 'TerminÃ©', 'Completed'];
        $aFaire = 0;
        $enCours = 0;
        $termines = 0;
        $enRetard = 0;
        $haute = 0;
        $moyenne = 0;
        $basse = 0;
        $statsByStatus = [];
        $statsByPriority = [];
        $assignmentsByWeek = [];
        $today = new \DateTimeImmutable('today');
        $weekCutoff = $today->modify('-8 weeks');

        foreach ($assignments as $assignment) {
            $status = (string) ($assignment->getStatut() ?? 'Unknown');
            $priority = (string) ($assignment->getPriorite() ?? 'Unknown');

            $statsByStatus[$status] = ($statsByStatus[$status] ?? 0) + 1;
            $statsByPriority[$priority] = ($statsByPriority[$priority] ?? 0) + 1;

            if ($status === 'À faire' || $status === 'A faire') {
                $aFaire++;
            } elseif ($status === 'En cours') {
                $enCours++;
            } elseif (in_array($status, $doneStatuses, true)) {
                $termines++;
            }

            if ($priority === 'Haute') {
                $haute++;
            } elseif ($priority === 'Moyenne') {
                $moyenne++;
            } elseif ($priority === 'Basse') {
                $basse++;
            }

            $dateFin = $assignment->getDateFin();
            if (
                $dateFin instanceof \DateTimeInterface
                && $dateFin < $today
                && !in_array($status, $doneStatuses, true)
            ) {
                $enRetard++;
            }

            $dateDebut = $assignment->getDateDebut();
            if ($dateDebut instanceof \DateTimeInterface && $dateDebut >= $weekCutoff) {
                $week = $dateDebut->format('o-W');
                $assignmentsByWeek[$week] = ($assignmentsByWeek[$week] ?? 0) + 1;
            }
        }

        $tauxCompletion = $total > 0 ? round(($termines / $total) * 100, 2) : 0;

        $chartData = [
            'labels' => array_keys($statsByStatus),
            'data' => array_values($statsByStatus),
            'colors' => $this->getStatusColors(array_keys($statsByStatus)),
        ];

        $priorityData = [
            'labels' => array_keys($statsByPriority),
            'data' => array_values($statsByPriority),
            'colors' => $this->getPriorityColors(array_keys($statsByPriority)),
        ];

        $progressRows = [];
        ksort($assignmentsByWeek);
        foreach ($assignmentsByWeek as $week => $count) {
            $progressRows[] = ['week' => $week, 'count' => $count];
        }

        $progressionData = $this->formatProgressionData($progressRows);

        return [
            'total' => $total,
            'aFaire' => $aFaire,
            'enCours' => $enCours,
            'termines' => $termines,
            'enRetard' => $enRetard,
            'haute' => $haute,
            'moyenne' => $moyenne,
            'basse' => $basse,
            'tauxCompletion' => $tauxCompletion,
            'chartData' => $chartData,
            'priorityData' => $priorityData,
            'progressionData' => $progressionData,
            'statsByStatus' => $statsByStatus,
            'statsByPriority' => $statsByPriority,
        ];
    }

    public function getSingleAssignmentStats(Assignment $assignment): array
    {
        $now = new \DateTime();
        $dateDebut = $assignment->getDateDebut();
        $dateFin = $assignment->getDateFin();

        $dureeTotale = $dateDebut && $dateFin ? $dateDebut->diff($dateFin)->days : 0;
        $tempsEcoule = $dateDebut ? $dateDebut->diff($now)->days : 0;
        $pourcentageTemps = $dureeTotale > 0 ? min(100, round(($tempsEcoule / $dureeTotale) * 100, 2)) : 0;
        $joursRestants = $dateFin ? max(0, $now->diff($dateFin)->days) : 0;
        $estEnRetard = $dateFin && $now > $dateFin && !in_array((string) $assignment->getStatut(), ['Termine', 'Terminé', 'TerminÃ©', 'Completed'], true);

        return [
            'dureeTotale' => $dureeTotale,
            'tempsEcoule' => $tempsEcoule,
            'pourcentageTemps' => $pourcentageTemps,
            'joursRestants' => $joursRestants,
            'estEnRetard' => $estEnRetard,
        ];
    }

    private function getStatusColors(array $statuts): array
    {
        $colorMap = [
            'À faire' => '#f59e0b',
            'A faire' => '#f59e0b',
            'En cours' => '#3b82f6',
            'TerminÃ©' => '#10b981',
            'Terminé' => '#10b981',
            'Termine' => '#10b981',
            'Completed' => '#10b981',
            'AnnulÃ©' => '#ef4444',
            'Annulé' => '#ef4444',
        ];

        $colors = [];
        foreach ($statuts as $statut) {
            $colors[] = $colorMap[$statut] ?? '#6b7280';
        }

        return $colors;
    }

    private function getPriorityColors(array $priorites): array
    {
        $colorMap = [
            'Haute' => '#ef4444',
            'Moyenne' => '#f59e0b',
            'Basse' => '#3b82f6',
        ];

        $colors = [];
        foreach ($priorites as $priorite) {
            $colors[] = $colorMap[$priorite] ?? '#6b7280';
        }

        return $colors;
    }

    private function formatProgressionData(array $assignmentsByWeek): array
    {
        $labels = [];
        $data = [];

        foreach ($assignmentsByWeek as $row) {
            $weekParts = explode('-', (string) $row['week']);
            if (count($weekParts) === 2) {
                $labels[] = 'S' . $weekParts[1] . '/' . $weekParts[0];
            } else {
                $labels[] = (string) $row['week'];
            }
            $data[] = (int) $row['count'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}

