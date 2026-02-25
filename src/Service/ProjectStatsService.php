<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;

class ProjectStatsService
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {
    }

    public function getProjectStats(User $user): array
    {
        $projects = $this->projectRepository->findByUserWithFilters($user, 'createdAt', 'DESC');
        $total = count($projects);

        $doneStatuses = ['Termine', 'Terminé', 'TerminÃ©', 'Completed'];
        $enCours = 0;
        $termines = 0;
        $enAttente = 0;
        $enRetard = 0;
        $statsByStatus = [];
        $projectsByMonth = [];
        $today = new \DateTimeImmutable('today');

        foreach ($projects as $project) {
            $status = (string) ($project->getStatut() ?? 'Unknown');
            $statsByStatus[$status] = ($statsByStatus[$status] ?? 0) + 1;

            if ($status === 'En cours') {
                $enCours++;
            } elseif (in_array($status, $doneStatuses, true)) {
                $termines++;
            } elseif ($status === 'En attente') {
                $enAttente++;
            }

            $dateFin = $project->getDateFin();
            if (
                $dateFin instanceof \DateTimeInterface
                && $dateFin < $today
                && !in_array($status, $doneStatuses, true)
            ) {
                $enRetard++;
            }

            $createdAt = $project->getCreatedAt();
            if ($createdAt instanceof \DateTimeInterface) {
                $month = $createdAt->format('Y-m');
                $projectsByMonth[$month] = ($projectsByMonth[$month] ?? 0) + 1;
            }
        }

        $tauxCompletion = $total > 0 ? round(($termines / $total) * 100, 2) : 0;

        $chartData = [
            'labels' => array_keys($statsByStatus),
            'data' => array_values($statsByStatus),
            'colors' => $this->getStatusColors(array_keys($statsByStatus)),
        ];

        $progressRows = [];
        ksort($projectsByMonth);
        foreach ($projectsByMonth as $month => $count) {
            $progressRows[] = ['month' => $month, 'count' => $count];
        }

        $progressionData = $this->formatProgressionData($progressRows);

        return [
            'total' => $total,
            'enCours' => $enCours,
            'termines' => $termines,
            'enAttente' => $enAttente,
            'enRetard' => $enRetard,
            'tauxCompletion' => $tauxCompletion,
            'chartData' => $chartData,
            'progressionData' => $progressionData,
            'statsByStatus' => $statsByStatus,
        ];
    }

    public function getSingleProjectStats(Project $project): array
    {
        $now = new \DateTime();
        $dateDebut = $project->getDateDebut();
        $dateFin = $project->getDateFin();

        $dureeTotale = $dateDebut && $dateFin ? $dateDebut->diff($dateFin)->days : 0;
        $tempsEcoule = $dateDebut ? $dateDebut->diff($now)->days : 0;
        $pourcentageTemps = $dureeTotale > 0 ? min(100, round(($tempsEcoule / $dureeTotale) * 100, 2)) : 0;
        $joursRestants = $dateFin ? max(0, $now->diff($dateFin)->days) : 0;
        $estEnRetard = $dateFin && $now > $dateFin && !in_array((string) $project->getStatut(), ['Termine', 'Terminé', 'TerminÃ©', 'Completed'], true);
        $nombreAssignments = count($project->getAssignments());

        return [
            'dureeTotale' => $dureeTotale,
            'tempsEcoule' => $tempsEcoule,
            'pourcentageTemps' => $pourcentageTemps,
            'joursRestants' => $joursRestants,
            'estEnRetard' => $estEnRetard,
            'nombreAssignments' => $nombreAssignments,
        ];
    }

    private function getStatusColors(array $statuts): array
    {
        $colorMap = [
            'En cours' => '#3b82f6',
            'TerminÃ©' => '#10b981',
            'Terminé' => '#10b981',
            'Termine' => '#10b981',
            'Completed' => '#10b981',
            'En attente' => '#f59e0b',
            'AnnulÃ©' => '#ef4444',
            'Annulé' => '#ef4444',
            'En pause' => '#6b7280',
        ];

        $colors = [];
        foreach ($statuts as $statut) {
            $colors[] = $colorMap[$statut] ?? '#94a3b8';
        }

        return $colors;
    }

    private function formatProgressionData(array $projectsByMonth): array
    {
        $labels = [];
        $data = [];

        foreach ($projectsByMonth as $row) {
            $date = \DateTime::createFromFormat('Y-m', (string) $row['month']);
            $labels[] = $date ? $date->format('M Y') : (string) $row['month'];
            $data[] = (int) $row['count'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}

