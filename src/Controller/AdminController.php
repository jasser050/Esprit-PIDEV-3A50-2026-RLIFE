<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\User;
use App\Form\DeckType;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function banUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $reason = $request->request->get('reason', 'Violation of terms of service');
        
        $user->setIsBanned(true);
        $user->setBannedAt(new \DateTimeImmutable());
        $user->setBanReason($reason);
        
        $entityManager->flush();
        
        $this->addFlash('success', sprintf('User %s has been banned.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/unban', name: 'app_admin_user_unban', methods: ['POST'])]
    public function unbanUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsBanned(false);
        $user->setBannedAt(null);
        $user->setBanReason(null);
        
        $entityManager->flush();
        
        $this->addFlash('success', sprintf('User %s has been unbanned.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/make-admin', name: 'app_admin_user_make_admin', methods: ['POST'])]
    public function makeAdmin(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf('User %s is now an admin.', $user->getEmail()));
        }
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/remove-admin', name: 'app_admin_user_remove_admin', methods: ['POST'])]
    public function removeAdmin(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_ADMIN');
        $user->setRoles($roles);
        $entityManager->flush();
        
        $this->addFlash('success', sprintf('Admin role removed from %s.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/statistics', name: 'app_admin_statistics')]
    public function statistics(EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
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
        
        $genderStats = $userRepository->createQueryBuilder('u')
            ->select('u.gender, COUNT(u.id) as count')
            ->groupBy('u.gender')
            ->getQuery()
            ->getResult();
        
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

    #[Route('/revision', name: 'app_admin_revision', methods: ['GET', 'POST'])]
    public function revision(
        Request $request,
        EntityManagerInterface $em,
        DeckRepository $deckRepository
    ): Response
    {
        $deck = new Deck();
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $deck->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload image : ' . $e->getMessage());
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $deck->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload PDF : ' . $e->getMessage());
                }
            }

            $deck->setUser($this->getUser());
            $deck->setDateCreation(new \DateTime());

            $em->persist($deck);
            $em->flush();

            $this->addFlash('success', 'Deck créé avec succès !');

            return $this->redirectToRoute('app_admin_revision');
        }

        $decks = $deckRepository->findAll();

        $recentChanges = [
            ['entity' => 'Deck', 'id' => 1, 'action' => 'created', 'by' => $this->getUser()->getEmail() ?? 'system', 'at' => new \DateTime('-5 minutes')],
        ];

        return $this->render('admin/revision.html.twig', [
            'form'           => $form->createView(),
            'recent_changes' => $recentChanges,
            'decks'          => $decks,
        ]);
    }
}