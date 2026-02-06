<?php

namespace App\Controller\Admin;

use App\Repository\AdminEmailLogRepository;
use App\Service\AdminMailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/emails')]
#[IsGranted('ROLE_ADMIN')]
class AdminEmailController extends AbstractController
{
    #[Route('/compose', name: 'app_admin_email_compose')]
    public function compose(Request $request, AdminMailerService $mailerService): Response
    {
        if ($request->isMethod('POST')) {
            $recipientType = $request->request->get('recipient_type');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');
            $sendTest = $request->request->get('send_test');

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

            // Send to recipients
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
}
