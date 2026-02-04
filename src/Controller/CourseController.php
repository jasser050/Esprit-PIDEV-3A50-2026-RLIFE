<?php

namespace App\Controller;

use App\Data\SampleData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/courses')]
class CourseController extends AbstractController
{
    #[Route('', name: 'app_courses')]
    public function index(): Response
    {
        return $this->render('pages/courses/index.html.twig', [
            'courses' => SampleData::getCourses(),
        ]);
    }

    #[Route('/new', name: 'app_courses_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // In a real app, save to database
            $this->addFlash('success', 'Course created successfully!');
            return $this->redirectToRoute('app_courses');
        }

        return $this->render('pages/courses/new.html.twig');
    }

    #[Route('/{id}', name: 'app_courses_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $courses = SampleData::getCourses();
        $course = null;
        foreach ($courses as $c) {
            if ($c['id'] === $id) {
                $course = $c;
                break;
            }
        }

        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $assignments = array_filter(SampleData::getAssignments(), fn($a) => $a['course_code'] === $course['code']);

        return $this->render('pages/courses/show.html.twig', [
            'course' => $course,
            'assignments' => $assignments,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_courses_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $courses = SampleData::getCourses();
        $course = null;
        foreach ($courses as $c) {
            if ($c['id'] === $id) {
                $course = $c;
                break;
            }
        }

        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        if ($request->isMethod('POST')) {
            // In a real app, update database
            $this->addFlash('success', 'Course updated successfully!');
            return $this->redirectToRoute('app_courses_show', ['id' => $id]);
        }

        return $this->render('pages/courses/edit.html.twig', [
            'course' => $course,
        ]);
    }
}
