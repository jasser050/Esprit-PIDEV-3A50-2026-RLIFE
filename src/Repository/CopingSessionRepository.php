<?php

namespace App\Repository;

use App\Entity\CopingSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

<<<<<<< HEAD
/**
 * @extends ServiceEntityRepository<CopingSession>
 */
=======
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
class CopingSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CopingSession::class);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
