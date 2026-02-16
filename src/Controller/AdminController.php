<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\User;
use App\Form\DeckType;
use App\Repository\DeckRepository;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        // Get statistics
        $userRepository = $entityManager->getRepository(User::class);
        
        $totalUsers = $userRepository->count([]);
        $activeUsers = $userRepository->count(['isBanned' => false]);
        $bannedUsers = $userRepository->count(['isBanned' => true]);
        $adminUsers = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Get recent users
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 10);
        
        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'banned_users' => $bannedUsers,
            'admin_users' => $adminUsers,
            'recent_users' => $recentUsers,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $entityManager, Request $request): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
        // Get filter from query params
        $filter = $request->query->get('filter', 'all');
        
        $queryBuilder = $userRepository->createQueryBuilder('u');
        
        if ($filter === 'banned') {
            $queryBuilder->where('u.isBanned = true');
        } elseif ($filter === 'active') {
            $queryBuilder->where('u.isBanned = false');
        } elseif ($filter === 'admin') {
            $queryBuilder->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%');
        }
        
        $users = $queryBuilder->orderBy('u.createdAt', 'DESC')->getQuery()->getResult();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'filter' => $filter,
        ]);
    }

    #[Route('/users/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(User $user, Request $request, EntityManagerInterface $entityManager, AuditLogService $auditLog, MailerInterface $mailer): Response
    {
        $reason = $request->request->get('reason', 'Violation of terms of service');
        
        $user->setIsBanned(true);
        $user->setBannedAt(new \DateTimeImmutable());
        $user->setBanReason($reason);
        
        $entityManager->flush();
        
        // Log the action
        try {
            $auditLog->logUserBan($user, $reason);
        } catch (\Exception $e) {
            // Silently continue if audit log fails
        }
        
        // Send automatic ban notification email
        try {
            $banEmail = (new Email())
                ->from('jasserbalti555@gmail.com')
                ->to($user->getEmail())
                ->subject('Account Suspended - RLIFE')
                ->html($this->getBanEmailHtml($user, $reason));
            
            $mailer->send($banEmail);
        } catch (\Exception $e) {
            // Log error but don't block the ban
        }
        
        $this->addFlash('success', sprintf('User %s has been banned and notified by email.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/unban', name: 'app_admin_user_unban', methods: ['POST'])]
    public function unbanUser(User $user, EntityManagerInterface $entityManager, AuditLogService $auditLog): Response
    {
        $user->setIsBanned(false);
        $user->setBannedAt(null);
        $user->setBanReason(null);
        
        $entityManager->flush();
        
        // Log the action
        try {
            $auditLog->logUserUnban($user);
        } catch (\Exception $e) {
            // Silently continue if audit log fails
        }
        
        $this->addFlash('success', sprintf('User %s has been unbanned.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/make-admin', name: 'app_admin_user_make_admin', methods: ['POST'])]
    public function makeAdmin(User $user, EntityManagerInterface $entityManager, AuditLogService $auditLog): Response
    {
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $entityManager->flush();
            
            // Log the action
            try {
     $auditLog->logUserPromotion($user);
            } catch (\Exception $e) {
    // Silently continue if audit log fails
        }
            
            
            $this->addFlash('success', sprintf('User %s is now an admin.', $user->getEmail()));
        }
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/remove-admin', name: 'app_admin_user_remove_admin', methods: ['POST'])]
    public function removeAdmin(User $user, EntityManagerInterface $entityManager, AuditLogService $auditLog): Response
    {
        $roles = array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_ADMIN');
        $user->setRoles($roles);
        $entityManager->flush();
        
        // Log the action
        
             try {
     $auditLog->logUserDemotion($user);
            } catch (\Exception $e) {
    // Silently continue if audit log fails
        }

        
        
        $this->addFlash('success', sprintf('Admin role removed from %s.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/statistics', name: 'app_admin_statistics')]
    public function statistics(EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
        // User growth statistics (last 7 days)
        $userGrowth = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $date->setTime(0, 0, 0);
            $nextDate = clone $date;
            $nextDate->modify('+1 day');
            
            $count = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt >= :start')
                ->andWhere('u.createdAt < :end')
                ->setParameter('start', $date)
                ->setParameter('end', $nextDate)
                ->getQuery()
                ->getSingleScalarResult();
            
            $userGrowth[] = [
                'date' => $date->format('M d'),
                'count' => $count,
            ];
        }
        
        // Gender distribution
        $genderStats = $userRepository->createQueryBuilder('u')
            ->select('u.gender, COUNT(u.id) as count')
            ->groupBy('u.gender')
            ->getQuery()
            ->getResult();
        
        // University distribution (top 5)
        $universityStats = $userRepository->createQueryBuilder('u')
            ->select('u.university, COUNT(u.id) as count')
            ->where('u.university IS NOT NULL')
            ->groupBy('u.university')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/statistics.html.twig', [
            'user_growth' => $userGrowth,
            'gender_stats' => $genderStats,
            'university_stats' => $universityStats,
        ]);
    }

    #[Route('/statistics/export', name: 'app_admin_statistics_export')]
    public function exportStatistics(EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
        // Get all statistics
        $totalUsers = $userRepository->count([]);
        $activeUsers = $userRepository->count(['isBanned' => false]);
        $bannedUsers = $userRepository->count(['isBanned' => true]);
        
        // User growth statistics (last 7 days)
        $userGrowth = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $date->setTime(0, 0, 0);
            $nextDate = clone $date;
            $nextDate->modify('+1 day');
            
            $count = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt >= :start')
                ->andWhere('u.createdAt < :end')
                ->setParameter('start', $date)
                ->setParameter('end', $nextDate)
                ->getQuery()
                ->getSingleScalarResult();
            
            $userGrowth[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $count,
            ];
        }
        
        // Gender distribution
        $genderStats = $userRepository->createQueryBuilder('u')
            ->select('u.gender, COUNT(u.id) as count')
            ->groupBy('u.gender')
            ->getQuery()
            ->getResult();
        
        // University distribution (all)
        $universityStats = $userRepository->createQueryBuilder('u')
            ->select('u.university, COUNT(u.id) as count')
            ->where('u.university IS NOT NULL')
            ->groupBy('u.university')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Create CSV content
        $csv = [];
        
        // Header
        $csv[] = "RLIFE Platform Statistics Report";
        $csv[] = "Generated: " . date('Y-m-d H:i:s');
        $csv[] = "";
        
        // Summary
        $csv[] = "SUMMARY";
        $csv[] = "Total Users," . $totalUsers;
        $csv[] = "Active Users," . $activeUsers;
        $csv[] = "Banned Users," . $bannedUsers;
        $csv[] = "";
        
        // User Growth
        $csv[] = "USER GROWTH (LAST 7 DAYS)";
        $csv[] = "Date,New Users";
        foreach ($userGrowth as $growth) {
            $csv[] = $growth['date'] . "," . $growth['count'];
        }
        $csv[] = "";
        
        // Gender Distribution
        $csv[] = "GENDER DISTRIBUTION";
        $csv[] = "Gender,Count,Percentage";
        foreach ($genderStats as $stat) {
            $percentage = round(($stat['count'] / $totalUsers) * 100, 2);
            $csv[] = ucfirst($stat['gender']) . "," . $stat['count'] . "," . $percentage . "%";
        }
        $csv[] = "";
        
        // University Distribution
        $csv[] = "UNIVERSITY DISTRIBUTION";
        $csv[] = "Rank,University,Students,Percentage";
        $rank = 1;
        foreach ($universityStats as $stat) {
            $percentage = round(($stat['count'] / $totalUsers) * 100, 2);
            $csv[] = $rank . "," . $stat['university'] . "," . $stat['count'] . "," . $percentage . "%";
            $rank++;
        }
        
        // Create response
        $response = new Response(implode("\n", $csv));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="rlife-statistics-' . date('Y-m-d') . '.csv"');
        
        return $response;
    }

    #[Route('/revision', name: 'app_admin_revision', methods: ['GET'])]
    public function revision(DeckRepository $deckRepository): Response
    {
        $decks = $deckRepository->findAll();

        return $this->render('admin/revision.html.twig', [
            'decks' => $decks,
        ]);
    }

    /**
     * Generate HTML for ban notification email
     */
    private function getBanEmailHtml(User $user, string $reason = null): string
    {
        $reasonText = $reason ? $reason : 'Violation of our Terms of Service';
        
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
                        background: linear-gradient(135deg, #ef4444 0%%, #dc2626 100%%);
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
                        padding: 25px;
                        border-radius: 8px;
                        border-left: 4px solid #ef4444;
                        margin: 20px 0;
                    }
                    .warning-box {
                        background: #fef2f2;
                        border: 2px solid #ef4444;
                        padding: 20px;
                        border-radius: 8px;
                        margin: 20px 0;
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
                    <h1 style="margin: 0; font-size: 28px;">⚠️ Account Suspended</h1>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;">RLIFE Platform</p>
                </div>
                <div class="content">
                    <div class="message">
                        <h2 style="color: #ef4444; margin-top: 0;">Dear %s,</h2>
                        <p>We are writing to inform you that your RLIFE account has been temporarily suspended.</p>
                    </div>
                    
                    <div class="warning-box">
                        <h3 style="color: #ef4444; margin-top: 0;">🚫 Reason for Suspension:</h3>
                        <p style="font-size: 16px;"><strong>%s</strong></p>
                    </div>
                    
                    <div class="message">
                        <h3>What This Means:</h3>
                        <ul>
                            <li>You will not be able to access your RLIFE account</li>
                            <li>Your data remains secure and will not be deleted</li>
                            <li>This action may be temporary or permanent depending on the situation</li>
                        </ul>
                    </div>
                    
                    <div class="message" style="background: #fef3c7; border-left-color: #f59e0b;">
                        <h3 style="margin-top: 0;">📞 Need Help?</h3>
                        <p>If you believe this is a mistake or would like to appeal this decision, please contact our support team immediately.</p>
                        <p><strong>Support Email:</strong> jasserbalti555@gmail.com</p>
                    </div>
                    
                    <div class="footer">
                        <p>Please review our Terms of Service and Community Guidelines.</p>
                        <p style="margin-top: 20px;">&copy; %d RLIFE. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ', $user->getFirstName(), $reasonText, date('Y'));
    }
}
