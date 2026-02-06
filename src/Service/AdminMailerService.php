<?php

namespace App\Service;

use App\Entity\AdminEmailLog;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Security;

class AdminMailerService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private AuditLogService $auditLog;
    private ?User $currentUser;

    public function __construct(
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        AuditLogService $auditLog,
        Security $security
    ) {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->auditLog = $auditLog;
        $this->currentUser = $security->getUser();
    }

    /**
     * Send email to all users
     */
    public function sendToAllUsers(string $subject, string $message): int
    {
        $users = $this->userRepository->findAll();
        return $this->sendToUsers($users, 'all', $subject, $message);
    }

    /**
     * Send email to active users only
     */
    public function sendToActiveUsers(string $subject, string $message): int
    {
        $users = $this->userRepository->findBy(['isBanned' => false]);
        return $this->sendToUsers($users, 'active', $subject, $message);
    }

    /**
     * Send email to banned users only
     */
    public function sendToBannedUsers(string $subject, string $message): int
    {
        $users = $this->userRepository->findBy(['isBanned' => true]);
        return $this->sendToUsers($users, 'banned', $subject, $message);
    }

    /**
     * Send email to admins only
     */
    public function sendToAdmins(string $subject, string $message): int
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();
        
        return $this->sendToUsers($users, 'admins', $subject, $message);
    }

    /**
     * Send email to a list of users
     *
     * @param User[] $users
     * @param string $recipientType
     * @param string $subject
     * @param string $message
     * @return int Number of emails sent
     */
    private function sendToUsers(array $users, string $recipientType, string $subject, string $messageBody): int
    {
        $sentCount = 0;

        foreach ($users as $user) {
            try {
                $email = (new Email())
                    ->from('noreply@rlife.com')
                    ->to($user->getEmail())
                    ->subject($subject)
                    ->html($this->formatEmailHtml($user, $messageBody));

                $this->mailer->send($email);
                $sentCount++;
            } catch (\Exception $e) {
                // Log error but continue sending to other users
                // You could log this to a file or error tracking system
                continue;
            }
        }

        // Log the email in database
        $this->logEmail($recipientType, $subject, $messageBody, $sentCount);

        // Log in audit trail
        $this->auditLog->logEmailSent($recipientType, $sentCount, $subject);

        return $sentCount;
    }

    /**
     * Format email HTML with nice styling
     */
    private function formatEmailHtml(User $user, string $message): string
    {
        return sprintf('
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%);
                        color: white;
                        padding: 30px;
                        border-radius: 10px 10px 0 0;
                        text-align: center;
                    }
                    .content {
                        background: #f9fafb;
                        padding: 30px;
                        border-radius: 0 0 10px 10px;
                    }
                    .message {
                        background: white;
                        padding: 20px;
                        border-radius: 8px;
                        border-left: 4px solid #667eea;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        color: #6b7280;
                        font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1 style="margin: 0;">RLIFE</h1>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;">Student Life Management Platform</p>
                </div>
                <div class="content">
                    <p>Hello <strong>%s</strong>,</p>
                    <div class="message">
                        %s
                    </div>
                    <div class="footer">
                        <p>This is an automated message from RLIFE Administration.</p>
                        <p>&copy; %d RLIFE. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ', $user->getFirstName(), nl2br(htmlspecialchars($message)), date('Y'));
    }

    /**
     * Log email to database
     */
    private function logEmail(string $recipientType, string $subject, string $message, int $count): void
    {
        $log = new AdminEmailLog();
        $log->setAdminUser($this->currentUser);
        $log->setRecipientType($recipientType);
        $log->setSubject($subject);
        $log->setMessage($message);
        $log->setRecipientCount($count);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * Send a test email to the current admin
     */
    public function sendTestEmail(string $subject, string $message): bool
    {
        try {
            $email = (new Email())
                ->from('noreply@rlife.com')
                ->to($this->currentUser->getEmail())
                ->subject('[TEST] ' . $subject)
                ->html($this->formatEmailHtml($this->currentUser, $message));

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
