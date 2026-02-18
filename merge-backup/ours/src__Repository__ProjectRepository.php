<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Récupère tous les projets d'un utilisateur avec filtres
     * Inclut les projets créés par l'utilisateur ET les projets partagés avec lui
     *
     * @param User $user
     * @param string $sort
     * @param string $direction
     * @param string $statut
     * @param string $search
     * @return Project[]
     */
    public function findByUserWithFilters(
        User $user,
        string $sort = 'createdAt',
        string $direction = 'DESC',
        string $statut = '',
        string $search = ''
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.shares', 'ps', 'WITH', 'ps.sharedWithUser = :user')
            ->addSelect('ps')
            ->andWhere('p.user = :user OR ps.id IS NOT NULL')
            ->setParameter('user', $user);

        // Filtre par statut
        if (!empty($statut)) {
            $qb->andWhere('p.statut = :statut')
               ->setParameter('statut', $statut);
        }

        // Recherche par titre ou description
        if (!empty($search)) {
            $qb->andWhere('p.titre LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Security: only allow known sortable fields
        $allowedFields = ['titre', 'dateDebut', 'dateFin', 'statut', 'createdAt'];

        if (!in_array($sort, $allowedFields, true)) {
            $sort = 'createdAt';
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy('p.' . $sort, $direction);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère tous les projets d'un utilisateur, triés
     *
     * @param User $user
     * @param string $sort
     * @param string $direction
     * @return Project[]
     */
    public function findByUser(User $user, string $sort = 'createdAt', string $direction = 'DESC'): array
    {
        return $this->findByUserWithFilters($user, $sort, $direction);
    }

    /**
     * Récupère les projets d'un utilisateur selon un statut donné
     *
     * @param User $user
     * @param string $statut
     * @return Project[]
     */
    public function findByUserAndStatus(User $user, string $statut): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', $statut)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de projets d'un utilisateur
     *
     * @param User $user
     * @return int
     */
    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les projets par statut
     *
     * @param User $user
     * @param string $statut
     * @return int
     */
    public function countByUserAndStatus(User $user, string $statut): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les projets à venir (non terminés et date de fin ≥ aujourd'hui)
     *
     * @param User $user
     * @return Project[]
     */
    public function findUpcomingByUser(User $user): array
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut != :statut')
            ->andWhere('p.dateFin >= :today OR p.dateFin IS NULL')
            ->setParameter('user', $user)
            ->setParameter('statut', 'Terminé')
            ->setParameter('today', $today)
            ->orderBy('p.dateFin', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets terminés d'un utilisateur
     *
     * @param User $user
     * @return Project[]
     */
    public function findCompletedByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', 'Terminé')
            ->orderBy('p.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets en retard (dateFin passée et non terminé)
     *
     * @param User $user
     * @return Project[]
     */
    public function findOverdueByUser(User $user): array
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut != :statut')
            ->andWhere('p.dateFin < :today')
            ->setParameter('user', $user)
            ->setParameter('statut', 'Terminé')
            ->setParameter('today', $today)
            ->orderBy('p.dateFin', 'DESC')
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
        $result = $this->createQueryBuilder('p')
            ->select('p.statut, COUNT(p.id) as count')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->groupBy('p.statut')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[$row['statut']] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Récupère les projets créés par mois (pour graphique)
     *
     * @param User $user
     * @param int $months Nombre de mois à afficher
     * @return array
     */
    public function getProjectsByMonth(User $user, int $months = 6): array
    {
        $startDate = new \DateTime("-{$months} months");
        
        $results = $this->createQueryBuilder('p')
            ->select('p.createdAt, COUNT(p.id) as count')
            ->andWhere('p.user = :user')
            ->andWhere('p.createdAt >= :startDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->groupBy('p.createdAt')
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Aggregate by month
        $monthlyData = [];
        foreach ($results as $result) {
            $month = $result['createdAt']->format('Y-m');
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = 0;
            }
            $monthlyData[$month] += (int)$result['count'];
        }
        
        // Convert to array of objects
        $formatted = [];
        foreach ($monthlyData as $month => $count) {
            $formatted[] = [
                'month' => $month,
                'count' => $count
            ];
        }
        
        return $formatted;
    }
   /**
 * Récupère TOUS les projets avec filtres (pour admin), incluant le propriétaire User
 *
 * @param string $sort
 * @param string $direction
 * @param string $statut
 * @param string $userEmail (filtre par email user)
 * @param string $search
 * @param int $limit (pour pagination)
 * @param int $offset
 * @return array [projects => Project[], total => int]
 */
public function findAllWithFilters(
    string $sort = 'createdAt',
    string $direction = 'DESC',
    string $statut = '',
    string $userEmail = '',
    string $search = '',
    int $limit = 20,
    int $offset = 0
): array {
    $qb = $this->createQueryBuilder('p')
        ->leftJoin('p.user', 'u')
        ->addSelect('u');

    if ($statut) {
        $qb->andWhere('p.statut = :statut')
           ->setParameter('statut', $statut);
    }

    if ($userEmail) {
        $qb->andWhere('u.email LIKE :email')
           ->setParameter('email', '%' . $userEmail . '%');
    }

    if ($search) {
        $qb->andWhere('p.titre LIKE :s OR p.description LIKE :s')
           ->setParameter('s', '%' . $search . '%');
    }

    /** ✅ CRITICAL FIX — VALIDATE SORT FIELD */
    $allowedFields = ['titre', 'dateDebut', 'dateFin', 'statut', 'createdAt'];

    if (!in_array($sort, $allowedFields, true)) {
        $sort = 'createdAt';
    }

    /** ✅ CRITICAL FIX — VALIDATE DIRECTION */
    $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

    $qb->orderBy('p.' . $sort, $direction);

    $countQb = clone $qb;

    $qb->setFirstResult($offset);
    $qb->setMaxResults($limit);

    $projects = $qb->getQuery()->getResult();

    $total = (int) $countQb
        ->select('COUNT(DISTINCT p.id)')
        ->resetDQLPart('orderBy')
        ->getQuery()
        ->getSingleScalarResult();

    return [
        'projects' => $projects,
        'total'    => $total,
    ];
}

}
