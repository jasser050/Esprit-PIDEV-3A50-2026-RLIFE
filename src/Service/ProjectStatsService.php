<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;

class ProjectStatsService
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {}

    /**
     * Récupère toutes les statistiques des projets d'un utilisateur
     *
     * @param User $user
     * @return array
     */
    public function getProjectStats(User $user): array
    {
        $total = $this->projectRepository->countByUser($user);
        $enCours = $this->projectRepository->countByUserAndStatus($user, 'En cours');
        $termines = $this->projectRepository->countByUserAndStatus($user, 'Terminé');
        $enAttente = $this->projectRepository->countByUserAndStatus($user, 'En attente');
        $enRetard = count($this->projectRepository->findOverdueByUser($user));

        // Statistiques par statut pour le graphique
        $statsByStatus = $this->projectRepository->getStatsByStatus($user);

        // Projets par mois pour le graphique de progression
        $projectsByMonth = $this->projectRepository->getProjectsByMonth($user, 6);

        // Calcul du taux de complétion
        $tauxCompletion = $total > 0 ? round(($termines / $total) * 100, 2) : 0;

        // Données pour le graphique circulaire (statuts)
        $chartData = [
            'labels' => array_keys($statsByStatus),
            'data' => array_values($statsByStatus),
            'colors' => $this->getStatusColors(array_keys($statsByStatus)),
        ];

        // Données pour le graphique de progression (par mois)
        $progressionData = $this->formatProgressionData($projectsByMonth);

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

    /**
     * Récupère les statistiques d'un projet spécifique
     *
     * @param Project $project
     * @return array
     */
    public function getSingleProjectStats(Project $project): array
    {
        $now = new \DateTime();
        $dateDebut = $project->getDateDebut();
        $dateFin = $project->getDateFin();

        // Calcul de la durée totale
        $dureeTotale = $dateDebut && $dateFin ? $dateDebut->diff($dateFin)->days : 0;

        // Calcul du temps écoulé
        $tempsEcoule = $dateDebut ? $dateDebut->diff($now)->days : 0;

        // Calcul du pourcentage de progression temporelle
        $pourcentageTemps = $dureeTotale > 0 
            ? min(100, round(($tempsEcoule / $dureeTotale) * 100, 2)) 
            : 0;

        // Nombre de jours restants
        $joursRestants = $dateFin ? max(0, $now->diff($dateFin)->days) : 0;

        // Statut du projet
        $estEnRetard = $dateFin && $now > $dateFin && $project->getStatut() !== 'Terminé';

        // Nombre d'assignments
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

    /**
     * Retourne les couleurs associées aux statuts
     *
     * @param array $statuts
     * @return array
     */
    private function getStatusColors(array $statuts): array
    {
        $colorMap = [
            'En cours' => '#3b82f6',      // Bleu
            'Terminé' => '#10b981',       // Vert
            'En attente' => '#f59e0b',    // Orange
            'Annulé' => '#ef4444',        // Rouge
            'En pause' => '#6b7280',      // Gris
        ];

        $colors = [];
        foreach ($statuts as $statut) {
            $colors[] = $colorMap[$statut] ?? '#94a3b8'; // Couleur par défaut
        }

        return $colors;
    }

    /**
     * Formate les données de progression par mois
     *
     * @param array $projectsByMonth
     * @return array
     */
    private function formatProgressionData(array $projectsByMonth): array
    {
        $labels = [];
        $data = [];

        foreach ($projectsByMonth as $row) {
            $date = \DateTime::createFromFormat('Y-m', $row['month']);
            $labels[] = $date ? $date->format('M Y') : $row['month'];
            $data[] = (int) $row['count'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}