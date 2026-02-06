<?php

namespace App\Controller;

use App\Data\SampleData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/assignments')]
class AssignmentController extends AbstractController
{
    #[Route('', name: 'app_assignments')]
    public function index(Request $request): Response
    {
        $assignments = SampleData::getAssignments();
        $filter = $request->query->get('filter', 'all');
        $sort = $request->query->get('sort', 'due_date');

        if ($filter !== 'all') {
            $assignments = array_filter($assignments, fn($a) => $a['status'] === $filter);
        }

        usort($assignments, function($a, $b) use ($sort) {
            if ($sort === 'priority') {
                $order = ['high' => 0, 'medium' => 1, 'low' => 2];
                return ($order[$a['priority']] ?? 3) - ($order[$b['priority']] ?? 3);
            }
            return strcmp($a['due_date'], $b['due_date']);
        });

        return $this->render('pages/assignments/index.html.twig', [
            'assignments' => $assignments,
            'current_filter' => $filter,
            'current_sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_assignments_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // In a real app, we would save the assignment here
            $this->addFlash('success', 'Assignment created successfully!');
            return $this->redirectToRoute('app_assignments');
        }

        $courses = SampleData::getCourses();
        $preselectedCourseId = $request->query->get('course_id');

        return $this->render('pages/assignments/new.html.twig', [
            'courses' => $courses,
            'preselected_course_id' => $preselectedCourseId,
        ]);
    }

    #[Route('/{id}', name: 'app_assignments_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $assignments = SampleData::getAssignments();
        $assignment = null;

        foreach ($assignments as $a) {
            if ($a['id'] === $id) {
                $assignment = $a;
                break;
            }
        }

        if (!$assignment) {
            throw $this->createNotFoundException('Assignment not found');
        }

        return $this->render('pages/assignments/show.html.twig', [
            'assignment' => $assignment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_assignments_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $assignments = SampleData::getAssignments();
        $assignment = null;

        foreach ($assignments as $a) {
            if ($a['id'] === $id) {
                $assignment = $a;
                break;
            }
        }

        if (!$assignment) {
            throw $this->createNotFoundException('Assignment not found');
        }

        if ($request->isMethod('POST')) {
            // In a real app, we would update the assignment here
            $this->addFlash('success', 'Assignment updated successfully!');
            return $this->redirectToRoute('app_assignments_show', ['id' => $id]);
        }

        $courses = SampleData::getCourses();

        return $this->render('pages/assignments/edit.html.twig', [
            'assignment' => $assignment,
            'courses' => $courses,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_assignments_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        // In a real app, we would delete the assignment here
        $this->addFlash('success', 'Assignment deleted successfully!');
        return $this->redirectToRoute('app_assignments');
    }
}
