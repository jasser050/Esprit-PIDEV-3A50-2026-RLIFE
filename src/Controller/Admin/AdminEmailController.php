<?php

namespace App\Controller\Admin;

use App\Entity\ScheduledEmail;
use App\Repository\AdminEmailLogRepository;
use App\Repository\ScheduledEmailRepository;
use App\Repository\UserRepository;
use App\Service\AdminMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/emails')]
#[IsGranted('ROLE_ADMIN')]
class AdminEmailController extends AbstractController
{
    #[Route('/compose', name: 'app_admin_email_compose')]
    public function compose(Request $request, AdminMailerService $mailerService, CsrfTokenManagerInterface $csrfTokenManager, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            // Validate CSRF token
            $token = $request->request->get('_token');
            if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('send_email', $token))) {
                $this->addFlash('error', 'Invalid CSRF token. Please try again.');
                return $this->redirectToRoute('app_admin_email_compose');
            }

            $recipientType = $request->request->get('recipient_type');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');
            $sendTest = $request->request->get('send_test');
            $scheduleEmail = $request->request->get('schedule_email');
            $scheduledAt = $request->request->get('scheduled_at');

            // Validate inputs
            if (empty($subject) || empty($message)) {
                $this->addFlash('error', 'Subject and message are required.');
                return $this->redirectToRoute('app_admin_email_compose');
            }

            // Send test email if requested
            if ($sendTest) {
                $success = $mailerService->sendTestEmail($subject, $message);
                if ($success) {
                    $this->addFlash('success', 'Test email sent to your email address.');
                } else {
                    $this->addFlash('error', 'Failed to send test email. Please check your email configuration.');
                }
                return $this->redirectToRoute('app_admin_email_compose');
            }

            // Check if email should be scheduled
            if ($scheduleEmail && !empty($scheduledAt)) {
                try {
                    $scheduledDateTime = new \DateTime($scheduledAt);
                    
                    // Validate scheduled time is in the future
                    if ($scheduledDateTime <= new \DateTime()) {
                        $this->addFlash('error', 'Scheduled time must be in the future.');
                        return $this->redirectToRoute('app_admin_email_compose');
                    }
                    
                    $scheduledEmailEntity = new ScheduledEmail();
                    $scheduledEmailEntity->setAdminUser($this->getUser());
                    $scheduledEmailEntity->setRecipientType($recipientType);
                    $scheduledEmailEntity->setSubject($subject);
                    $scheduledEmailEntity->setMessage($message);
                    $scheduledEmailEntity->setScheduledAt($scheduledDateTime);
                    
                    $em->persist($scheduledEmailEntity);
                    $em->flush();
                    
                    $this->addFlash('success', sprintf('Email scheduled for %s. It will be sent automatically.', $scheduledDateTime->format('Y-m-d H:i')));
                    return $this->redirectToRoute('app_admin_email_scheduled');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to schedule email: ' . $e->getMessage());
                    return $this->redirectToRoute('app_admin_email_compose');
                }
            }

            // Send to recipients immediately
            try {
                $sentCount = match ($recipientType) {
                    'all' => $mailerService->sendToAllUsers($subject, $message),
                    'active' => $mailerService->sendToActiveUsers($subject, $message),
                    'banned' => $mailerService->sendToBannedUsers($subject, $message),
                    'admins' => $mailerService->sendToAdmins($subject, $message),
                    default => 0,
                };

                if ($sentCount > 0) {
                    $this->addFlash('success', sprintf('Email sent successfully to %d users!', $sentCount));
                } else {
                    $this->addFlash('warning', 'No users found in the selected category.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to send emails: ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_admin_email_history');
        }

        return $this->render('admin/emails/compose.html.twig');
    }

    #[Route('/history', name: 'app_admin_email_history')]
    public function history(AdminEmailLogRepository $emailLogRepository): Response
    {
        $emails = $emailLogRepository->findAllOrdered();
        $totalEmails = count($emails);
        $emailsLast7Days = $emailLogRepository->countForLastDays(7);
        $totalRecipients = $emailLogRepository->getTotalRecipientsCount();

        return $this->render('admin/emails/history.html.twig', [
            'emails' => $emails,
            'total_emails' => $totalEmails,
            'emails_last_7_days' => $emailsLast7Days,
            'total_recipients' => $totalRecipients,
        ]);
    }

    #[Route('/recipient-count', name: 'app_admin_email_recipient_count', methods: ['GET'])]
    public function getRecipientCount(Request $request, UserRepository $userRepository): JsonResponse
    {
        $recipientType = $request->query->get('type', 'all');

        $count = match ($recipientType) {
            'all' => $userRepository->count([]),
            'active' => $userRepository->count(['isBanned' => false]),
            'banned' => $userRepository->count(['isBanned' => true]),
            'admins' => $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->getQuery()
                ->getSingleScalarResult(),
            default => 0,
        };

        return new JsonResponse([
            'count' => $count,
            'type' => $recipientType
        ]);
    }

    #[Route('/scheduled', name: 'app_admin_email_scheduled')]
    public function scheduled(ScheduledEmailRepository $scheduledEmailRepository): Response
    {
        $scheduledEmails = $scheduledEmailRepository->findAllOrdered();
        $pendingCount = $scheduledEmailRepository->countByStatus('pending');
        $sentCount = $scheduledEmailRepository->countByStatus('sent');
        $failedCount = $scheduledEmailRepository->countByStatus('failed');

        return $this->render('admin/emails/scheduled.html.twig', [
            'scheduled_emails' => $scheduledEmails,
            'pending_count' => $pendingCount,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);
    }

    #[Route('/scheduled/{id}/cancel', name: 'app_admin_email_cancel_scheduled', methods: ['POST'])]
    public function cancelScheduled(ScheduledEmail $scheduledEmail, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager, Request $request): Response
    {
        // Validate CSRF token
        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('cancel_scheduled_' . $scheduledEmail->getId(), $token))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_email_scheduled');
        }

        if (!$scheduledEmail->canBeCancelled()) {
            $this->addFlash('error', 'This scheduled email cannot be cancelled.');
            return $this->redirectToRoute('app_admin_email_scheduled');
        }

        $scheduledEmail->setStatus('cancelled');
        $em->flush();

        $this->addFlash('success', 'Scheduled email cancelled successfully.');
        return $this->redirectToRoute('app_admin_email_scheduled');
    }
}
