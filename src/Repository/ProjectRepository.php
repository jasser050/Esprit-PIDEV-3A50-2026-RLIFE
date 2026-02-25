<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectShare;
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
     * @return Project[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Project[]
     */
    public function findByUserWithFilters(
        User $user,
        string $sort = 'createdAt',
        string $direction = 'DESC',
        string $statut = '',
        string $search = ''
    ): array {
        $allowedSortFields = ['titre', 'dateDebut', 'dateFin', 'statut', 'createdAt'];
        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'createdAt';
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p')
            ->leftJoin(
                ProjectShare::class,
                'ps',
                'WITH',
                'ps.project = p AND ps.sharedWithUser = :user'
            )
            ->andWhere('p.user = :user OR ps.id IS NOT NULL')
            ->setParameter('user', $user);

        if ($statut !== '') {
            $qb->andWhere('p.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($search !== '') {
            $qb->andWhere('p.titre LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('p.' . $sort, $direction)
            ->getQuery()
            ->getResult();
    }

    /**
     * Admin listing with filters, sorting and pagination.
     *
     * @return array{projects: Project[], total: int}
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
        $allowedSortFields = ['titre', 'createdAt', 'dateDebut', 'dateFin', 'statut'];
        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'createdAt';
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if ($statut !== '') {
            $qb->andWhere('p.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($userEmail !== '') {
            $qb->andWhere('LOWER(u.email) LIKE :userEmail')
                ->setParameter('userEmail', '%' . mb_strtolower($userEmail) . '%');
        }

        if ($search !== '') {
            $qb->andWhere('(LOWER(p.titre) LIKE :search OR LOWER(COALESCE(p.description, \'\')) LIKE :search)')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $projects = $qb->orderBy('p.' . $sort, $direction)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'projects' => $projects,
            'total' => $total,
        ];
    }

    /**
     * @return Project[]
     */
    public function findByUserAndStatus(User $user, string $statut): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', $statut)
            ->orderBy('p.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

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
     * @return Project[]
     */
    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut NOT IN (:doneStatuses)')
            ->andWhere('p.dateFin >= :today')
            ->setParameter('user', $user)
            ->setParameter('doneStatuses', ['Termine', 'Terminé', 'TerminÃ©', 'Completed'])
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('p.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Project[]
     */
    public function findOverdueByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.statut NOT IN (:doneStatuses)')
            ->andWhere('p.dateFin < :today')
            ->setParameter('user', $user)
            ->setParameter('doneStatuses', ['Termine', 'Terminé', 'TerminÃ©', 'Completed'])
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('p.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    public function getStatsByStatus(User $user): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.statut AS statut, COUNT(p.id) AS cnt')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->groupBy('p.statut')
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($rows as $row) {
            $label = (string) ($row['statut'] ?? 'Unknown');
            $stats[$label] = (int) ($row['cnt'] ?? 0);
        }

        return $stats;
    }

    /**
     * @return array<int, array{month:string,count:int}>
     */
    public function getProjectsByMonth(User $user, int $months = 6): array
    {
        $start = (new \DateTimeImmutable('first day of this month'))->modify('-' . max(1, $months - 1) . ' months');

        $projects = $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.createdAt >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($projects as $project) {
            $createdAt = $project->getCreatedAt();
            if (!$createdAt instanceof \DateTimeInterface) {
                continue;
            }

            $month = $createdAt->format('Y-m');
            $grouped[$month] = ($grouped[$month] ?? 0) + 1;
        }

        ksort($grouped);

        $result = [];
        foreach ($grouped as $month => $count) {
            $result[] = [
                'month' => (string) $month,
                'count' => (int) $count,
            ];
        }

        return $result;
    }

    /**
     * @return array{projects: Project[], total: int}
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
        $allowedSortFields = ['titre', 'createdAt', 'dateFin', 'statut'];
        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'createdAt';
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if ($statut !== '') {
            $qb->andWhere('p.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($userEmail !== '') {
            $qb->andWhere('LOWER(u.email) LIKE :userEmail')
                ->setParameter('userEmail', '%' . mb_strtolower(trim($userEmail)) . '%');
        }

        if ($search !== '') {
            $qb->andWhere('LOWER(p.titre) LIKE :search OR LOWER(p.description) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower(trim($search)) . '%');
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $projects = $qb
            ->orderBy('p.' . $sort, $direction)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'projects' => $projects,
            'total' => $total,
        ];
    }
}
