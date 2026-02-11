<?php

namespace App\Repository;

use App\Entity\AssignmentCollaborator;
use App\Entity\Assignment;
use App\Entity\User;
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
     * Find all collaborators for an assignment
     *
     * @param Assignment $assignment
     * @return AssignmentCollaborator[]
     */
    public function findByAssignment(Assignment $assignment): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.assignment = :assignment')
            ->setParameter('assignment', $assignment)
            ->orderBy('ac.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all assignments assigned to a user
     *
     * @param User $user
     * @return AssignmentCollaborator[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ac.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a user is assigned to an assignment
     *
     * @param Assignment $assignment
     * @param User $user
     * @return AssignmentCollaborator|null
     */
    public function findOneByAssignmentAndUser(Assignment $assignment, User $user): ?AssignmentCollaborator
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