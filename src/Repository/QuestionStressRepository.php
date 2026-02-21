<?php

namespace App\Repository;

use App\Entity\QuestionStress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

<<<<<<< HEAD
/**
 * @extends ServiceEntityRepository<QuestionStress>
 */
=======
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
class QuestionStressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionStress::class);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
