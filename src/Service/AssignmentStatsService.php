<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\AssignmentRepository;

class AssignmentStatsService
{
    public function __construct(
        private AssignmentRepository $assignmentRepository
    ) {}

    /**
     * Récupère toutes les statistiques des assignments d'un utilisateur
     *
     * @param User $user
     * @return array
     */
    public function getAssignmentStats(User $user): array
    {
        $total    = $this->assignmentRepository->countByUser($user);
        $aFaire   = $this->assignmentRepository->countByUserAndStatus($user, 'À faire');
        $enCours  = $this->assignmentRepository->countByUserAndStatus($user, 'En cours');
        $termines = $this->assignmentRepository->countByUserAndStatus($user, 'Terminé');
        $enRetard = count($this->assignmentRepository->findOverdueByUser($user));

        // Statistiques par priorité
        $haute   = $this->assignmentRepository->countByUserAndPriority($user, 'Haute');
        $moyenne = $this->assignmentRepository->countByUserAndPriority($user, 'Moyenne');
        $basse   = $this->assignmentRepository->countByUserAndPriority($user, 'Basse');

        // Statistiques par statut pour le graphique
        $statsByStatus = $this->assignmentRepository->getStatsByStatus($user);

        // Statistiques par priorité pour le graphique
        $statsByPriority = $this->assignmentRepository->getStatsByPriority($user);

        // Assignments par semaine pour le graphique de progression
        $assignmentsByWeek = $this->assignmentRepository->getAssignmentsByWeek($user, 8);

        // Calcul du taux de complétion
        $tauxCompletion = $total > 0 ? round(($termines / $total) * 100, 2) : 0;

        // Données pour le graphique circulaire (statuts)
        $chartData = [
            'labels' => array_keys($statsByStatus),
            'data'   => array_values($statsByStatus),
            'colors' => $this->getStatusColors(array_keys($statsByStatus)),
        ];

        // Données pour le graphique de priorités
        $priorityData = [
            'labels' => array_keys($statsByPriority),
            'data'   => array_values($statsByPriority),
            'colors' => $this->getPriorityColors(array_keys($statsByPriority)),
        ];

        // Données pour le graphique de progression (par semaine)
        $progressionData = $this->formatProgressionData($assignmentsByWeek);

        return [
            'total'           => $total,
            'aFaire'          => $aFaire,
            'enCours'         => $enCours,
            'termines'        => $termines,
            'enRetard'        => $enRetard,
            'haute'           => $haute,
            'moyenne'         => $moyenne,
            'basse'           => $basse,
            'tauxCompletion'  => $tauxCompletion,
            'chartData'       => $chartData,
            'priorityData'    => $priorityData,
            'progressionData' => $progressionData,
            'statsByStatus'   => $statsByStatus,
            'statsByPriority' => $statsByPriority,
        ];
    }

    /**
     * Récupère les statistiques d'un assignment spécifique
     *
     * @param Assignment $assignment
     * @return array
     */
    public function getSingleAssignmentStats(Assignment $assignment): array
    {
        $now = new \DateTime();
        $dateDebut = $assignment->getDateDebut();
        $dateFin = $assignment->getDateFin();

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

        // Statut de l'assignment
        $estEnRetard = $dateFin && $now > $dateFin && $assignment->getStatut() !== 'Terminé';

        return [
            'dureeTotale'      => $dureeTotale,
            'tempsEcoule'      => $tempsEcoule,
            'pourcentageTemps' => $pourcentageTemps,
            'joursRestants'    => $joursRestants,
            'estEnRetard'      => $estEnRetard,
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
            'À faire'  => '#f59e0b',  // Orange/Warning
            'En cours' => '#3b82f6',  // Bleu/Primary
            'Terminé'  => '#10b981',  // Vert/Success
            'Annulé'   => '#ef4444',  // Rouge/Danger
        ];

        $colors = [];
        foreach ($statuts as $statut) {
            $colors[] = $colorMap[$statut] ?? '#6b7280'; // Couleur par défaut
        }

        return $colors;
    }

    /**
     * Retourne les couleurs associées aux priorités
     *
     * @param array $priorites
     * @return array
     */
    private function getPriorityColors(array $priorites): array
    {
        $colorMap = [
            'Haute'   => '#ef4444',  // Rouge
            'Moyenne' => '#f59e0b',  // Orange
            'Basse'   => '#3b82f6',  // Bleu
        ];

        $colors = [];
        foreach ($priorites as $priorite) {
            $colors[] = $colorMap[$priorite] ?? '#6b7280';
        }

        return $colors;
    }

    /**
     * Formate les données de progression par semaine
     *
     * @param array $assignmentsByWeek
     * @return array
     */
    private function formatProgressionData(array $assignmentsByWeek): array
    {
        $labels = [];
        $data = [];

        foreach ($assignmentsByWeek as $row) {
            // Format: YYYY-WW (année-semaine)
            $weekParts = explode('-', $row['week']);
            if (count($weekParts) === 2) {
                $year = $weekParts[0];
                $week = $weekParts[1];
                $labels[] = "S{$week}/{$year}";
            } else {
                $labels[] = $row['week'];
            }
            $data[] = (int) $row['count'];
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }
}