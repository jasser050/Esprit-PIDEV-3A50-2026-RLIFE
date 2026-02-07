<?php

namespace App\Service;

use App\Entity\AdminAuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class AuditLogService
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private ?User $currentUser;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->currentUser = $security->getUser();
    }

    /**
     * Log an admin action
     *
     * @param string $actionType Action type (e.g., 'user_banned', 'project_deleted')
     * @param string|null $targetType Type of target (e.g., 'user', 'project')
     * @param int|null $targetId ID of the target
     * @param string|null $description Human-readable description
     * @return AdminAuditLog
     */
    public function log(
        string $actionType,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $description = null
    ): AdminAuditLog {
        $log = new AdminAuditLog();
        $log->setAdminUser($this->currentUser);
        $log->setActionType($actionType);
        $log->setTargetType($targetType);
        $log->setTargetId($targetId);
        $log->setDescription($description);

        
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }

    /**
     * Log user ban
     */
    public function logUserBan(User $user, string $reason): AdminAuditLog
    {
        return $this->log(
            'user_banned',
            'user',
            $user->getId(),
            sprintf('Banned user "%s" (%s). Reason: %s', $user->getFullName(), $user->getEmail(), $reason)
        );
    }

    /**
     * Log user unban
     */
    public function logUserUnban(User $user): AdminAuditLog
    {
        return $this->log(
            'user_unbanned',
            'user',
            $user->getId(),
            sprintf('Unbanned user "%s" (%s)', $user->getFullName(), $user->getEmail())
        );
    }

    /**
     * Log user promotion to admin
     */
    public function logUserPromotion(User $user): AdminAuditLog
    {
        return $this->log(
            'user_promoted',
            'user',
            $user->getId(),
            sprintf('Promoted user "%s" (%s) to administrator', $user->getFullName(), $user->getEmail())
        );
    }

    /**
     * Log admin role removal
     */
    public function logUserDemotion(User $user): AdminAuditLog
    {
        return $this->log(
            'user_demoted',
            'user',
            $user->getId(),
            sprintf('Removed admin role from "%s" (%s)', $user->getFullName(), $user->getEmail())
        );
    }

    /**
     * Log project deletion
     */
    public function logProjectDeletion(int $projectId, string $title, string $reason): AdminAuditLog
    {
        return $this->log(
            'project_deleted',
            'project',
            $projectId,
            sprintf('Deleted project "%s" (ID: %d). Reason: %s', $title, $projectId, $reason)
        );
    }

    /**
     * Log assignment deletion
     */
    public function logAssignmentDeletion(int $assignmentId, string $title, string $reason): AdminAuditLog
    {
        return $this->log(
            'assignment_deleted',
            'assignment',
            $assignmentId,
            sprintf('Deleted assignment "%s" (ID: %d). Reason: %s', $title, $assignmentId, $reason)
        );
    }

    /**
     * Log deck deletion
     */
    public function logDeckDeletion(int $deckId, string $title, string $reason): AdminAuditLog
    {
        return $this->log(
            'deck_deleted',
            'deck',
            $deckId,
            sprintf('Deleted deck "%s" (ID: %d). Reason: %s', $title, $deckId, $reason)
        );
    }

    /**
     * Log flashcard deletion
     */
    public function logFlashcardDeletion(int $flashcardId, string $title, string $reason): AdminAuditLog
    {
        return $this->log(
            'flashcard_deleted',
            'flashcard',
            $flashcardId,
            sprintf('Deleted flashcard "%s" (ID: %d). Reason: %s', $title, $flashcardId, $reason)
        );
    }

    /**
     * Log email sent
     */
    public function logEmailSent(string $recipientType, int $recipientCount, string $subject): AdminAuditLog
    {
        return $this->log(
            'email_sent',
            'email',
            null,
            sprintf('Sent email to %d %s users. Subject: "%s"', $recipientCount, $recipientType, $subject)
        );
    }
}
