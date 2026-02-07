<?php

namespace App\Repository;

use App\Entity\AdminAuditLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminAuditLog>
 */
class AdminAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminAuditLog::class);
    }

    /**
     * Find all logs ordered by most recent
     *
     * @return AdminAuditLog[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by admin user
     *
     * @param User $admin
     * @return AdminAuditLog[]
     */
    public function findByAdmin(User $admin): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.adminUser = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by action type
     *
     * @param string $actionType
     * @return AdminAuditLog[]
     */
    public function findByActionType(string $actionType): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.actionType = :actionType')
            ->setParameter('actionType', $actionType)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs for a specific target
     *
     * @param string $targetType
     * @param int $targetId
     * @return AdminAuditLog[]
     */
    public function findByTarget(string $targetType, int $targetId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.targetType = :targetType')
            ->andWhere('a.targetId = :targetId')
            ->setParameter('targetType', $targetType)
            ->setParameter('targetId', $targetId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs within a date range
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return AdminAuditLog[]
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent logs (last N)
     *
     * @param int $limit
     * @return AdminAuditLog[]
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count logs by action type
     *
     * @return array
     */
    public function countByActionType(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.actionType, COUNT(a.id) as count')
            ->groupBy('a.actionType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics for the last N days
     *
     * @param int $days
     * @return int
     */
    public function getStatisticsForLastDays(int $days = 7): int
    {
        $startDate = new \DateTime("-$days days");
        $startDate->setTime(0, 0, 0);
        
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
