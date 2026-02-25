<?php

namespace App\Repository;

use App\Entity\WellBeing;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WellBeing>
 */
class WellBeingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WellBeing::class);
    }

    /**
     * @return WellBeing[]
     */
    public function findRecentByUser(User $user, array $orderBy = ['entryDate' => 'DESC'], ?int $limit = null): array
    {
        return $this->findBy(['user' => $user], $orderBy, $limit);
    }
}
