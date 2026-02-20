<?php

namespace App\Repository;

use App\Entity\ProjectShare;
use App\Entity\Project;
use App\Entity\User;
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
     * Find all users with whom a project is shared
     *
     * @param Project $project
     * @return ProjectShare[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.project = :project')
            ->setParameter('project', $project)
            ->orderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all projects shared with a user
     *
     * @param User $user
     * @return ProjectShare[]
     */
    public function findBySharedWithUser(User $user): array
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.sharedWithUser = :user')
            ->setParameter('user', $user)
            ->orderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a project is shared with a specific user
     *
     * @param Project $project
     * @param User $user
     * @return ProjectShare|null
     */
    public function findOneByProjectAndUser(Project $project, User $user): ?ProjectShare
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
     * Check if user has access to project (owner or shared)
     *
     * @param Project $project
     * @param User $user
     * @return bool
     */
    public function hasAccess(Project $project, User $user): bool
    {
        // Owner always has access
        if ($project->getUser() === $user) {
            return true;
        }

        // Check if shared
        $share = $this->findOneByProjectAndUser($project, $user);
        return $share !== null;
    }
}