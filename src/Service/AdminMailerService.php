<?php

namespace App\Service;

use App\Entity\AdminEmailLog;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
    private LoggerInterface $logger;

    public function __construct(
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        AuditLogService $auditLog,
        Security $security,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->auditLog = $auditLog;
        $this->currentUser = $security->getUser();
        $this->logger = $logger;
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
        $failedEmails = [];

        $this->logger->info(sprintf('Starting to send email to %d users (type: %s)', count($users), $recipientType));

        foreach ($users as $user) {
            try {
                $email = (new Email())
                    ->from('jasserbalti555@gmail.com')
                    ->to($user->getEmail())
                    ->subject($subject)
                    ->html($this->formatEmailHtml($user, $messageBody));

                $this->mailer->send($email);
                $sentCount++;
                
                $this->logger->info(sprintf('✅ Email sent to: %s', $user->getEmail()));
            } catch (\Exception $e) {
                // Log error but continue sending to other users
                $failedEmails[] = $user->getEmail();
                $this->logger->error(sprintf('❌ Failed to send email to %s: %s', $user->getEmail(), $e->getMessage()));
                continue;
            }
        }

        if (!empty($failedEmails)) {
            $this->logger->warning(sprintf('Failed to send to %d recipients: %s', count($failedEmails), implode(', ', $failedEmails)));
        }

        // Log the email in database
        if ($this->currentUser) {
            $this->logEmail($recipientType, $subject, $messageBody, $sentCount);
            
            // Log in audit trail
            $this->auditLog->logEmailSent($recipientType, $sentCount, $subject);
        }

        $this->logger->info(sprintf('Email sending complete. Sent: %d, Failed: %d', $sentCount, count($failedEmails)));

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
            $this->logger->info(sprintf('Sending test email to: %s', $this->currentUser->getEmail()));
            
            $email = (new Email())
                ->from('jasserbalti555@gmail.com')
                ->to($this->currentUser->getEmail())
                ->subject('[TEST] ' . $subject)
                ->html($this->formatEmailHtml($this->currentUser, $message));

            $this->mailer->send($email);
            
            $this->logger->info('✅ Test email sent successfully');
            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('❌ Test email failed: %s', $e->getMessage()));
            return false;
        }
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(User $user): bool
    {
        try {
            $this->logger->info(sprintf('Sending welcome email to: %s', $user->getEmail()));
            
            $email = (new Email())
                ->from('jasserbalti555@gmail.com')
                ->to($user->getEmail())
                ->subject('Welcome to RLIFE - Your Account is Ready!')
                ->html($this->formatWelcomeEmailHtml($user));

            $this->mailer->send($email);
            
            $this->logger->info('✅ Welcome email sent successfully');
            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('❌ Welcome email failed: %s', $e->getMessage()));
            return false;
        }
    }

    /**
     * Format welcome email HTML
     */
    private function formatWelcomeEmailHtml(User $user): string
    {
        return sprintf('
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    .footer { text-align: center; color: #888; font-size: 12px; margin-top: 30px; }
                    .highlight { background: #fff; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>Welcome to RLIFE! 🎉</h1>
                    </div>
                    <div class="content">
                        <h2>Hello %s!</h2>
                        <p>Thank you for joining RLIFE, your comprehensive student life management platform.</p>
                        
                        <div class="highlight">
                            <strong>Your account is now active!</strong><br>
                            Email: <strong>%s</strong><br>
                            Username: <strong>%s</strong>
                        </div>

                        <p>With RLIFE, you can:</p>
                        <ul>
                            <li>📚 Manage your courses and assignments</li>
                            <li>📅 Plan your study schedule</li>
                            <li>🎯 Track your academic progress</li>
                            <li>📝 Create and study flashcards</li>
                            <li>💪 Monitor your wellbeing</li>
                        </ul>

                        <p style="text-align: center;">
                            <a href="http://localhost:8000/dashboard" class="button">Go to Dashboard</a>
                        </p>

                        <p>If you have any questions or need assistance, feel free to reach out to our support team.</p>
                        
                        <p>Best regards,<br><strong>The RLIFE Team</strong></p>
                    </div>
                    <div class="footer">
                        <p>This is an automated message from RLIFE.</p>
                        <p>&copy; %d RLIFE. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ', $user->getFirstName(), $user->getEmail(), $user->getUsername(), date('Y'));
    }
}
