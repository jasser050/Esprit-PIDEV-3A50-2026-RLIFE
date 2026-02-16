<?php

namespace App\Repository;

use App\Entity\ProjectShare;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectShare>
 */
class ProjectShareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectShare::class);
    }

    /**
     * Find shares by project
     */
    public function findByProject($project): array
    {
        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.sharedWithUser', 'swu')
            ->leftJoin('ps.sharedByUser', 'sbu')
            ->addSelect('swu', 'sbu')
            ->andWhere('ps.project = :project')
            ->setParameter('project', $project)
            ->orderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find shares by user (projects shared with this user)
     */
    public function findBySharedWithUser($user): array
    {
        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.project', 'p')
            ->leftJoin('ps.sharedByUser', 'sbu')
            ->leftJoin('p.user', 'pu')
            ->addSelect('p', 'sbu', 'pu')
            ->andWhere('ps.sharedWithUser = :user')
            ->setParameter('user', $user)
            ->orderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find one by project and user
     */
    public function findOneByProjectAndUser($project, $user): ?ProjectShare
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.project = :project')
            ->andWhere('ps.sharedWithUser = :user')
            ->setParameter('project', $project)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if user has access to project
     */
    public function hasAccess($project, $user): bool
    {
        $share = $this->findOneByProjectAndUser($project, $user);
        return $share !== null;
    }
}
