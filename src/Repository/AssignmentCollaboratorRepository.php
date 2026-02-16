<?php

namespace App\Repository;

use App\Entity\AssignmentCollaborator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssignmentCollaborator>
 */
class AssignmentCollaboratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssignmentCollaborator::class);
    }

    /**
     * Find collaborators by assignment
     */
    public function findByAssignment($assignment): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.assignment = :assignment')
            ->setParameter('assignment', $assignment)
            ->orderBy('ac.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find collaborations by user (assignments assigned to this user)
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ac.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find one by assignment and user
     */
    public function findOneByAssignmentAndUser($assignment, $user): ?AssignmentCollaborator
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.assignment = :assignment')
            ->andWhere('ac.user = :user')
            ->setParameter('assignment', $assignment)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
