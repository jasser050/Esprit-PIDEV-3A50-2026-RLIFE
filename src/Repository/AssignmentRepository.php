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
     * @return Assignment[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
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
        $allowedSortFields = ['titre', 'dateDebut', 'dateFin', 'priorite', 'statut', 'createdAt'];
        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'dateFin';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user);

        if ($priorite !== '') {
            $qb->andWhere('a.priorite = :priorite')
                ->setParameter('priorite', $priorite);
        }

        if ($statut !== '') {
            $qb->andWhere('a.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($search !== '') {
            $qb->andWhere('a.titre LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('a.' . $sort, $direction)
            ->getQuery()
            ->getResult();
    }

    /**
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

    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

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
     * @return Assignment[]
     */
    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut NOT IN (:doneStatuses)')
            ->andWhere('a.dateFin >= :today')
            ->setParameter('user', $user)
            ->setParameter('doneStatuses', ['Termine', 'Terminé', 'TerminÃ©', 'Completed'])
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('a.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Assignment[]
     */
    public function findOverdueByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.statut NOT IN (:doneStatuses)')
            ->andWhere('a.dateFin < :today')
            ->setParameter('user', $user)
            ->setParameter('doneStatuses', ['Termine', 'Terminé', 'TerminÃ©', 'Completed'])
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('a.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    public function getStatsByStatus(User $user): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.statut AS statut, COUNT(a.id) AS cnt')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->groupBy('a.statut')
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
     * @return array<string, int>
     */
    public function getStatsByPriority(User $user): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.priorite AS priorite, COUNT(a.id) AS cnt')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->groupBy('a.priorite')
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($rows as $row) {
            $label = (string) ($row['priorite'] ?? 'Unknown');
            $stats[$label] = (int) ($row['cnt'] ?? 0);
        }

        return $stats;
    }

    /**
     * @return array<int, array{week:string,count:int}>
     */
    public function getAssignmentsByWeek(User $user, int $weeks = 8): array
    {
        $start = (new \DateTimeImmutable('today'))->modify('-' . max(1, $weeks) . ' weeks');

        $assignments = $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.dateDebut >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->orderBy('a.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($assignments as $assignment) {
            $dateDebut = $assignment->getDateDebut();
            if (!$dateDebut instanceof \DateTimeInterface) {
                continue;
            }

            $week = $dateDebut->format('o-W');
            $grouped[$week] = ($grouped[$week] ?? 0) + 1;
        }

        ksort($grouped);

        $result = [];
        foreach ($grouped as $week => $count) {
            $result[] = [
                'week' => (string) $week,
                'count' => (int) $count,
            ];
        }

        return $result;
    }
}
