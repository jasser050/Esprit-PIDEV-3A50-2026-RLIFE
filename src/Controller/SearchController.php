<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Matiere;
use App\Entity\Project;
use App\Entity\Assignment;
use App\Entity\Deck;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/api/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json(['results' => []]);
        }
        
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['results' => []], 401);
        }
        
        $results = [];
        
        // Search Courses
        $courses = $em->getRepository(Matiere::class)->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.nomMatiere LIKE :query OR m.code LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        foreach ($courses as $course) {
            $results[] = [
                'type' => 'course',
                'icon' => 'book-open',
                'title' => $course->getNomMatiere(),
                'subtitle' => $course->getCode() ?? 'Course',
                'url' => $this->generateUrl('app_courses'),
                'color' => 'blue'
            ];
        }
        
        // Search Projects
        $projects = $em->getRepository(Project::class)->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.titre LIKE :query OR p.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        foreach ($projects as $project) {
            $results[] = [
                'type' => 'project',
                'icon' => 'folder',
                'title' => $project->getTitre(),
                'subtitle' => 'Project',
                'url' => $this->generateUrl('app_project_show', ['id' => $project->getId()]),
                'color' => 'purple'
            ];
        }
        
        // Search Assignments (owned by current user)
        $assignments = $em->getRepository(Assignment::class)->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.titre LIKE :query OR a.description LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        foreach ($assignments as $assignment) {
            $results[] = [
                'type' => 'assignment',
                'icon' => 'clipboard-check',
                'title' => $assignment->getTitre(),
                'subtitle' => 'Assignment',
                'url' => $this->generateUrl('app_assignments_show', ['id' => $assignment->getId()]),
                'color' => 'green'
            ];
        }
        
        // Search Decks
        $decks = $em->getRepository(Deck::class)->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.titre LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        foreach ($decks as $deck) {
            $results[] = [
                'type' => 'deck',
                'icon' => 'layers',
                'title' => $deck->getTitre(),
                'subtitle' => 'Flashcard Deck',
                'url' => $this->generateUrl('app_revisions'),
                'color' => 'amber'
            ];
        }
        
        // Search Pages/Quick Actions
        $pages = [
            ['title' => 'Dashboard', 'route' => 'app_dashboard', 'icon' => 'layout-dashboard'],
            ['title' => 'Courses', 'route' => 'app_courses', 'icon' => 'book-open'],
            ['title' => 'Projects', 'route' => 'app_project_index', 'icon' => 'folder'],
            ['title' => 'Assignments', 'route' => 'app_assignments', 'icon' => 'clipboard-check'],
            ['title' => 'Planning', 'route' => 'app_planning', 'icon' => 'calendar'],
            ['title' => 'Revisions', 'route' => 'app_revisions', 'icon' => 'layers'],
            ['title' => 'Wellbeing', 'route' => 'app_wellbeing', 'icon' => 'heart'],
            ['title' => 'Settings', 'route' => 'app_settings', 'icon' => 'settings'],
        ];
        
        foreach ($pages as $page) {
            if (stripos($page['title'], $query) !== false) {
                $results[] = [
                    'type' => 'page',
                    'icon' => $page['icon'],
                    'title' => $page['title'],
                    'subtitle' => 'Page',
                    'url' => $this->generateUrl($page['route']),
                    'color' => 'slate'
                ];
            }
        }
        
        // Search Other Users (if you want social features)
        $users = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.id != :currentUser')
            ->andWhere('u.username LIKE :query OR u.firstName LIKE :query OR u.lastName LIKE :query')
            ->setParameter('currentUser', $user->getId())
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
        
        foreach ($users as $foundUser) {
            $results[] = [
                'type' => 'user',
                'icon' => 'user',
                'title' => $foundUser->getFullName(),
                'subtitle' => '@' . $foundUser->getUsername(),
                'url' => '#', // Add user profile route if you have one
                'color' => 'indigo'
            ];
        }
        
        return $this->json(['results' => $results]);
    }
}
