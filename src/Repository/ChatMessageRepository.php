<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * Get recent chat history for a user
     *
     * @param User $user
     * @param int $limit
     * @return ChatMessage[]
     */
    public function getRecentHistory(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Clear chat history for a user
     */
    public function clearHistoryForUser(User $user): void
    {
        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
