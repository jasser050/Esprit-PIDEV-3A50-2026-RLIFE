<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Matiere;
use App\Form\MatiereType;
use App\Repository\MatiereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        EntityManagerInterface $entityManager,
        MatiereRepository $matiereRepository
    ): Response {
        // User statistics
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
        
        // Recent users
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 10);
        
        // Course statistics
        $totalCourses = $matiereRepository->count([]);
        $popularCourses = $this->getPopularCourses($matiereRepository);
        $coursesBySection = $this->getCoursesBySection($matiereRepository);
        
        // Platform statistics (à implémenter selon vos besoins)
        $totalAssignments = 0;
        $activeSessions = 0;
        $totalFlashcards = 0;
        
        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'banned_users' => $bannedUsers,
            'admin_users' => $adminUsers,
            'recent_users' => $recentUsers,
            'total_courses' => $totalCourses,
            'total_assignments' => $totalAssignments,
            'active_sessions' => $activeSessions,
            'total_flashcards' => $totalFlashcards,
            'popular_courses' => $popularCourses,
            'courses_by_section' => $coursesBySection,
        ]);
    }

    // ==================== USERS MANAGEMENT ====================
    
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

    // ==================== COURSES MANAGEMENT ====================
    
    #[Route('/courses', name: 'app_admin_courses')]
    public function courses(MatiereRepository $matiereRepository, Request $request): Response
    {
        $filter = $request->query->get('filter', 'all');
        $section = $request->query->get('section');
        
        $queryBuilder = $matiereRepository->createQueryBuilder('m');
        
        if ($section) {
            $queryBuilder->where('m.sectionMatiere = :section')
                ->setParameter('section', $section);
        }
        
        $matieres = $queryBuilder
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Get all sections for filter
        $sections = $matiereRepository->createQueryBuilder('m')
            ->select('DISTINCT m.sectionMatiere')
            ->orderBy('m.sectionMatiere', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/courses/index.html.twig', [
    'matieres' => $matieres,
    'sections' => array_column($sections, 'sectionMatiere'),
    'current_section' => $section,
]);
    }

    #[Route('/courses/{id}', name: 'app_admin_courses_show', requirements: ['id' => '\d+'])]
    public function showCourse(Matiere $matiere, MatiereRepository $matiereRepository): Response
    {
        // Count how many users have this course
        $userCount = $matiereRepository->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.user)')
            ->where('m.code = :code')
            ->setParameter('code', $matiere->getCode())
            ->getQuery()
            ->getSingleScalarResult();
        
        // Get users who have this course
        $users = $matiereRepository->createQueryBuilder('m')
            ->select('DISTINCT u')
            ->join('m.user', 'u')
            ->where('m.code = :code')
            ->setParameter('code', $matiere->getCode())
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/courses/show.html.twig', [
            'matiere' => $matiere,
            'user_count' => $userCount,
            'users' => $users,
        ]);
    }

   #[Route('/courses/{id}/delete', name: 'app_admin_courses_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
public function deleteCourse(
    Matiere $matiere,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // Vérification CSRF
    if (!$this->isCsrfTokenValid('delete' . $matiere->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Invalid security token.');
        return $this->redirectToRoute('app_admin_courses');
    }

    // Supprimer la matière directement sans toucher aux relations
    try {
        $entityManager->remove($matiere);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Course "%s" has been deleted.', $matiere->getNomMatiere()));
    } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
        // Si la matière est liée ailleurs, afficher un message d’erreur
        $this->addFlash('error', 'Cannot delete this course because it is linked to other data.');
    }

    return $this->redirectToRoute('app_admin_courses');
}




    #[Route('/courses/bulk-delete', name: 'app_admin_courses_bulk_delete', methods: ['POST'])]
public function bulkDeleteCourses(
    Request $request,
    EntityManagerInterface $entityManager,
    MatiereRepository $matiereRepository
): Response {
    $ids = $request->request->all('ids');

    if (empty($ids)) {
        $this->addFlash('error', 'No courses selected.');
        return $this->redirectToRoute('app_admin_courses');
    }

    if (!$this->isCsrfTokenValid('bulk_delete', $request->request->get('_token'))) {
        $this->addFlash('error', 'Invalid security token.');
        return $this->redirectToRoute('app_admin_courses');
    }

    $count = 0;
    foreach ($ids as $id) {
        $matiere = $matiereRepository->find($id);
        if ($matiere) {
            try {
                $entityManager->remove($matiere);
                $count++;
            } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
                // Si la matière est liée ailleurs, on ignore et continue
                continue;
            }
        }
    }

    $entityManager->flush();

    $this->addFlash('success', sprintf('%d course(s) deleted successfully.', $count));

    return $this->redirectToRoute('app_admin_courses');
}

   
    // ==================== STATISTICS ====================
    
    #[Route('/statistics', name: 'app_admin_statistics')]
    public function statistics(
        EntityManagerInterface $entityManager,
        MatiereRepository $matiereRepository
    ): Response {
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
        
        // Course statistics
        $coursesGrowth = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $date->setTime(0, 0, 0);
            $nextDate = clone $date;
            $nextDate->modify('+1 day');
            
            $count = $matiereRepository->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('m.createdAt >= :start')
                ->andWhere('m.createdAt < :end')
                ->setParameter('start', $date)
                ->setParameter('end', $nextDate)
                ->getQuery()
                ->getSingleScalarResult();
            
            $coursesGrowth[] = [
                'date' => $date->format('M d'),
                'count' => $count,
            ];
        }
        
        return $this->render('admin/statistics.html.twig', [
            'user_growth' => $userGrowth,
            'gender_stats' => $genderStats,
            'university_stats' => $universityStats,
            'courses_growth' => $coursesGrowth,
        ]);
    }

    // ==================== PRIVATE HELPER METHODS ====================
    
    private function getPopularCourses(MatiereRepository $matiereRepository): array
    {
        return $matiereRepository->createQueryBuilder('m')
            ->select('m.code', 'm.nomMatiere', 'm.sectionMatiere', 'COUNT(m.id) as user_count')
            ->groupBy('m.code', 'm.nomMatiere', 'm.sectionMatiere')
            ->orderBy('user_count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
    
    private function getCoursesBySection(MatiereRepository $matiereRepository): array
    {
        $results = $matiereRepository->createQueryBuilder('m')
            ->select('m.sectionMatiere as section', 'COUNT(DISTINCT m.id) as count', 'COUNT(DISTINCT m.user) as total_users')
            ->groupBy('m.sectionMatiere')
            ->getQuery()
            ->getResult();
        
        $colors = ['primary', 'success', 'warning', 'danger', 'accent', 'secondary'];
        $colorIndex = 0;
        
        foreach ($results as &$result) {
            $result['color'] = $colors[$colorIndex % count($colors)];
            $result['section'] = $result['section'] ?? 'Non classé';
            $colorIndex++;
        }
        
        return $results;
    }

   #[Route('/courses/new', name: 'app_admin_courses_new')]
public function newCourse(Request $request, EntityManagerInterface $entityManager): Response
{
    $matiere = new Matiere();

    // Création du formulaire
    $form = $this->createForm(MatiereType::class, $matiere);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // Vérifier si le code existe déjà
        $existing = $entityManager->getRepository(Matiere::class)
            ->findOneBy(['code' => $matiere->getCode()]);

        if ($existing) {
            $this->addFlash('error', 'A course with this code already exists.');
            return $this->redirectToRoute('app_admin_courses_new');
        }

        // Assignation automatique du user connecté
        $matiere->setUser($this->getUser());

        // Gestion des dates
        $matiere->setCreatedAt(new \DateTime());
        $matiere->setUpdatedAt(null); // sera mis à jour automatiquement si modifié plus tard

        // Persister et sauvegarder
        $entityManager->persist($matiere);
        $entityManager->flush();

        $this->addFlash('success', 'Course created successfully');

        return $this->redirectToRoute('app_admin_courses');
    }

    return $this->render('admin/courses/new.html.twig', [
        'form' => $form->createView(),
    ]);
}



#[Route('/courses/{id}/edit', name: 'app_admin_courses_edit', requirements: ['id' => '\d+'])]
public function edit(
    Request $request,
    Matiere $matiere,
    EntityManagerInterface $entityManager
): Response {
    $form = $this->createForm(MatiereType::class, $matiere);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $matiere->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Course updated successfully.');

        return $this->redirectToRoute('app_admin_courses');
    }

    return $this->render('admin/courses/edit.html.twig', [
        'form' => $form,
        'matiere' => $matiere,
    ]);
}

}
