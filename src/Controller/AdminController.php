<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function banUser(User $user, Request $request, EntityManagerInterface $entityManager, AuditLogService $auditLog): Response
    {
        $reason = $request->request->get('reason', 'Violation of terms of service');
        
        $user->setIsBanned(true);
        $user->setBannedAt(new \DateTimeImmutable());
        $user->setBanReason($reason);
        
        $entityManager->flush();
        
        // Log the action
        $auditLog->logUserBan($user, $reason);
        
        $this->addFlash('success', sprintf('User %s has been banned.', $user->getEmail()));
        
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
        $auditLog->logUserUnban($user);
        
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
            $auditLog->logUserPromotion($user);
            
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
        $auditLog->logUserDemotion($user);
        
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
    public function statisticsExport(EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
        // Get user growth data
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
        
        // Get gender statistics
        $genderStats = $userRepository->createQueryBuilder('u')
            ->select('u.gender, COUNT(u.id) as count')
            ->groupBy('u.gender')
            ->getQuery()
            ->getResult();
        
        // Get university statistics
        $universityStats = $userRepository->createQueryBuilder('u')
            ->select('u.university, COUNT(u.id) as count')
            ->where('u.university IS NOT NULL')
            ->groupBy('u.university')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Get total users
        $totalUsers = $userRepository->count([]);
        
        // Create CSV content
        $csv = [];
        $csv[] = ['RLIFE Platform Statistics Report'];
        $csv[] = ['Generated on: ' . (new \DateTime())->format('Y-m-d H:i:s')];
        $csv[] = [];
        
        // Total users
        $csv[] = ['Total Users', $totalUsers];
        $csv[] = [];
        
        // User Growth
        $csv[] = ['User Growth (Last 7 Days)'];
        $csv[] = ['Date', 'New Users'];
        foreach ($userGrowth as $data) {
            $csv[] = [$data['date'], $data['count']];
        }
        $csv[] = [];
        
        // Gender Distribution
        $csv[] = ['Gender Distribution'];
        $csv[] = ['Gender', 'Count'];
        foreach ($genderStats as $data) {
            $csv[] = [$data['gender'] ?? 'Not Specified', $data['count']];
        }
        $csv[] = [];
        
        // University Distribution
        $csv[] = ['Top Universities'];
        $csv[] = ['University', 'Count'];
        foreach ($universityStats as $data) {
            $csv[] = [$data['university'], $data['count']];
        }
        
        // Generate CSV string
        $csvContent = '';
        foreach ($csv as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }
        
        // Create response
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'rlife_statistics_' . date('Y-m-d_His') . '.csv'
            )
        );
        
        return $response;
    }
}
