<?php

namespace App\Controller;

use App\Data\SampleData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/projects')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'app_projects')]
    public function index(): Response
    {
        $projects = SampleData::getProjects();
        $tasks = SampleData::getKanbanTasks();

        // Group tasks by project
        $tasksByProject = [];
        foreach ($tasks as $task) {
            $pid = $task['project_id'];
            if (!isset($tasksByProject[$pid])) {
                $tasksByProject[$pid] = ['todo' => [], 'in_progress' => [], 'done' => []];
            }
            $tasksByProject[$pid][$task['status']][] = $task;
        }

        return $this->render('pages/projects/index.html.twig', [
            'projects' => $projects,
            'tasks_by_project' => $tasksByProject,
        ]);
    }

    #[Route('/new', name: 'app_projects_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // In a real app, we would save the project to the database
            $this->addFlash('success', 'Project created successfully!');
            return $this->redirectToRoute('app_projects');
        }

        return $this->render('pages/projects/new.html.twig', [
            'courses' => SampleData::getCourses(),
        ]);
    }

    #[Route('/{id}', name: 'app_projects_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $projects = SampleData::getProjects();
        $project = null;
        foreach ($projects as $p) {
            if ($p['id'] === $id) {
                $project = $p;
                break;
            }
        }

        if (!$project) {
            throw $this->createNotFoundException('Project not found');
        }

        // Get tasks for this project
        $allTasks = SampleData::getKanbanTasks();
        $tasks = ['todo' => [], 'in_progress' => [], 'done' => []];
        foreach ($allTasks as $task) {
            if ($task['project_id'] === $id) {
                $tasks[$task['status']][] = $task;
            }
        }

        $totalTasks = count($tasks['todo']) + count($tasks['in_progress']) + count($tasks['done']);

        return $this->render('pages/projects/show.html.twig', [
            'project' => $project,
            'tasks' => $tasks,
            'total_tasks' => $totalTasks,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_projects_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $projects = SampleData::getProjects();
        $project = null;
        foreach ($projects as $p) {
            if ($p['id'] === $id) {
                $project = $p;
                break;
            }
        }

        if (!$project) {
            throw $this->createNotFoundException('Project not found');
        }

        if ($request->isMethod('POST')) {
            // In a real app, we would update the project in the database
            $this->addFlash('success', 'Project updated successfully!');
            return $this->redirectToRoute('app_projects_show', ['id' => $id]);
        }

        return $this->render('pages/projects/edit.html.twig', [
            'project' => $project,
            'courses' => SampleData::getCourses(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_projects_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id): Response
    {
        $projects = SampleData::getProjects();
        $project = null;
        foreach ($projects as $p) {
            if ($p['id'] === $id) {
                $project = $p;
                break;
            }
        }

        if (!$project) {
            throw $this->createNotFoundException('Project not found');
        }

        // In a real app, we would delete the project from the database
        $this->addFlash('success', 'Project deleted successfully!');
        return $this->redirectToRoute('app_projects');
    }
}
