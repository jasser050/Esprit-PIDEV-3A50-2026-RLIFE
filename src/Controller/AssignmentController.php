<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Form\AssignmentType;
use App\Repository\AssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assignments')]
#[IsGranted('ROLE_USER')]
class AssignmentController extends AbstractController
{
    #[Route('', name: 'app_assignments', methods: ['GET'])]
    public function index(AssignmentRepository $assignmentRepository): Response
    {
        $assignments = $assignmentRepository->findByUser($this->getUser());

        return $this->render('pages/assignments/index.html.twig', [
            'assignments' => $assignments,
        ]);
    }

   #[Route('/new', name: 'app_assignments_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $assignment = new Assignment();

    // Si on vient d'un projet
    if ($projectId = $request->query->get('project_id')) {
        $project = $entityManager->getRepository(Project::class)->find($projectId);
        if ($project && $project->getUser() === $this->getUser()) {
            $assignment->setProject($project);
        }
    }

    $form = $this->createForm(AssignmentType::class, $assignment, [
        'user' => $this->getUser(),
    ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $assignment->setUser($this->getUser());

            $entityManager->persist($assignment);
            $entityManager->flush();

            $this->addFlash('success', 'Assignment créé avec succès !');

            return $this->redirectToRoute('app_assignments');
        }

        return $this->render('pages/assignments/new.html.twig', [
            'assignment' => $assignment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_assignments_show', methods: ['GET'])]
    public function show(Assignment $assignment): Response
    {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('pages/assignments/show.html.twig', [
            'assignment' => $assignment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_assignments_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AssignmentType::class, $assignment, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Assignment modifié avec succès !');

            return $this->redirectToRoute('app_assignments');
        }

        return $this->render('pages/assignments/edit.html.twig', [
            'assignment' => $assignment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_assignments_delete', methods: ['POST'])]
    public function delete(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$assignment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($assignment);
            $entityManager->flush();

            $this->addFlash('success', 'Assignment supprimé avec succès !');
        }

        return $this->redirectToRoute('app_assignments');
    }
}