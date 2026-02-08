<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\AssignmentRepository;    
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/project')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        // Récupérer uniquement les projets de l'utilisateur connecté
        $projects = $projectRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('pages/projects/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer le projet à l'utilisateur connecté
            $project->setUser($this->getUser());
            
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet créé avec succès!');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('pages/projects/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

   #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
public function show(Project $project, AssignmentRepository $assignmentRepository): Response
{
    // Vérifier que l'utilisateur est propriétaire du projet
    if ($project->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException();
    }

    // Récupérer les assignments de ce projet
    $assignments = $assignmentRepository->findByProject($project);

    return $this->render('pages/projects/show.html.twig', [
        'project' => $project,
        'assignments' => $assignments,        // ← on ajoute ça
    ]);
}

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire du projet
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Projet modifié avec succès!');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('pages/projects/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire du projet
        if ($project->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet supprimé avec succès!');
        }

        return $this->redirectToRoute('app_project_index');
    }
}