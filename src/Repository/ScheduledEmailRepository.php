<?php

namespace App\Repository;

use App\Entity\ScheduledEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduledEmail>
 */
class ScheduledEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledEmail::class);
    }

    /**
     * Find all scheduled emails ordered by scheduled date
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending emails that are due to be sent
     */
    public function findDueEmails(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.scheduledAt <= :now')
            ->setParameter('status', 'pending')
            ->setParameter('now', new \DateTime())
            ->orderBy('s.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending scheduled emails
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('s.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count scheduled emails by status
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
