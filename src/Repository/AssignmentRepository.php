<?php

namespace App\Repository;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assignment>
 */
class AssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

    /**
     * Récupère tous les assignments d'un utilisateur avec filtres
     *
     * @param User $user
     * @param string $sort
     * @param string $direction
     * @param string $priorite
     * @param string $statut
     * @param string $search
     * @return Assignment[]
     */
    public function findByUserWithFilters(
        User $user,
        string $sort = 'dateFin',
        string $direction = 'ASC',
        string $priorite = '',
        string $statut = '',
        string $search = ''
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.project', 'p')
            ->addSelect('p')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user);

        // Filtre par priorité
        if (!empty($priorite)) {
            $qb->andWhere('a.priorite = :priorite')
               ->setParameter('priorite', $priorite);
        }

        // Filtre par statut
        if (!empty($statut)) {
            $qb->andWhere('a.statut = :statut')
               ->setParameter('statut', $statut);
        }

        // Recherche par titre ou description
        if (!empty($search)) {
            $qb->andWhere('a.titre LIKE :search OR a.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Security: only allow known sortable fields
        $allowedFields = ['titre', 'dateDebut', 'dateFin', 'priorite', 'statut', 'createdAt'];

        if (!in_array($sort, $allowedFields, true)) {
            $sort = 'dateFin';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb->orderBy('a.' . $sort, $direction);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all assignments for a specific user
     *
     * @param User $user
     * @param string $sort
     * @param string $direction
     * @return Assignment[]
     */
    public function findByUser(User $user, string $sort = 'dateFin', string $direction = 'ASC'): array
    {
        return $this->findByUserWithFilters($user, $sort, $direction);
    }

    /**
     * Find assignments by project
     *
     * @param Project $project
     * @return Assignment[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.project = :project')
            ->setParameter('project', $project)
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assignments by priority for a user
     *
     * @param User $user
     * @param string $priorite
     * @return Assignment[]
     */
    public function findByUserAndPriority(User $user, string $priorite): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.priorite = :priorite')
            ->setParameter('user', $user)
            ->setParameter('priorite', $priorite)
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assignments by status for a user
     *
     * @param User $user
     * @param string $statut
     * @return Assignment[]
     */
    public function findByUserAndStatus(User $user, string $statut): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', $statut)
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total assignments for a user
     *
     * @param User $user
     * @return int
     */
    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les assignments par statut
     *
     * @param User $user
     * @param string $statut
     * @return int
     */
    public function countByUserAndStatus(User $user, string $statut): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les assignments par priorité
     *
     * @param User $user
     * @param string $priorite
     * @return int
     */
    public function countByUserAndPriority(User $user, string $priorite): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.user = :user')
            ->andWhere('a.priorite = :priorite')
            ->setParameter('user', $user)
            ->setParameter('priorite', $priorite)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find upcoming assignments (not yet completed)
     *
     * @param User $user
     * @return Assignment[]
     */
    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut != :statut')
            ->andWhere('a.dateFin >= :today')
            ->setParameter('user', $user)
            ->setParameter('statut', 'Terminé')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find overdue assignments
     *
     * @param User $user
     * @return Assignment[]
     */
    public function findOverdueByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut != :statut')
            ->andWhere('a.dateFin < :today')
            ->setParameter('user', $user)
            ->setParameter('statut', 'Terminé')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('a.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques par statut pour un utilisateur
     *
     * @param User $user
     * @return array
     */
    public function getStatsByStatus(User $user): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('a.statut, COUNT(a.id) as count')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->groupBy('a.statut')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[$row['statut']] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Récupère les statistiques par priorité pour un utilisateur
     *
     * @param User $user
     * @return array
     */
    public function getStatsByPriority(User $user): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('a.priorite, COUNT(a.id) as count')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->groupBy('a.priorite')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[$row['priorite']] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Récupère les assignments créés par semaine (pour graphique)
     *
     * @param User $user
     * @param int $weeks Nombre de semaines à afficher
     * @return array
     */
    public function getAssignmentsByWeek(User $user, int $weeks = 8): array
    {
        $startDate = new \DateTime("-{$weeks} weeks");

        $result = $this->createQueryBuilder('a')
            ->select('a.createdAt')
            ->andWhere('a.user = :user')
            ->andWhere('a.createdAt >= :startDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Group the results by week
        $weeklyData = [];
        foreach ($result as $row) {
            $week = $row['createdAt']->format('Y-W');

            if (!isset($weeklyData[$week])) {
                $weeklyData[$week] = 0;
            }

            $weeklyData[$week]++;
        }

        // Format the results
        $formattedResult = [];
        foreach ($weeklyData as $week => $count) {
            $formattedResult[] = [
                'week' => $week,
                'count' => $count
            ];
        }

        return $formattedResult;
    }
    #[Route('/assignment/{id}', name: 'app_assignment_show', methods: ['GET'])]
    public function show(
        Assignment $assignment,
        AssignmentRepository $assignmentRepository   // ← Correction ici
    ): Response {
        // Exemple d'utilisation
        // $relatedAssignments = $assignmentRepository->findBy(['project' => $assignment->getProject()]);

        return $this->render('assignment/show.html.twig', [
            'assignment' => $assignment,
        ]);
    }
}