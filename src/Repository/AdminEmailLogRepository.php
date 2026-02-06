<?php

namespace App\Repository;

use App\Entity\AdminEmailLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminEmailLog>
 */
class AdminEmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminEmailLog::class);
    }

    /**
     * Find all emails ordered by most recent
     *
     * @return AdminEmailLog[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics for last N days
     *
     * @param int $days
     * @return int
     */
    public function countForLastDays(int $days = 7): int
    {
        $startDate = new \DateTime("-$days days");
        $startDate->setTime(0, 0, 0);
        
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.sentAt >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get total recipients count
     *
     * @return int
     */
    public function getTotalRecipientsCount(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('SUM(e.recipientCount)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
