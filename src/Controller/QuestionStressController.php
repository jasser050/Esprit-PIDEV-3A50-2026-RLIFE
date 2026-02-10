<?php

namespace App\Controller;

use App\Entity\QuestionStress;
use App\Form\QuestionStress1Type;
use App\Repository\QuestionStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/question/stress')]
final class QuestionStressController extends AbstractController
{
    #[Route(name: 'app_question_stress_index', methods: ['GET'])]
    public function index(QuestionStressRepository $questionStressRepository): Response
    {
        return $this->render('admin/question_stress/index.html.twig', [
            'questionStresses' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_question_stress_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $questionStress = new QuestionStress();
        $form = $this->createForm(QuestionStress1Type::class, $questionStress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($questionStress);
            $entityManager->flush();

            return $this->redirectToRoute('app_question_stress_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('question_stress/new.html.twig', [
            'question_stress' => $questionStress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_stress_show', methods: ['GET'])]
    public function show(QuestionStress $questionStress): Response
    {
        return $this->render('question_stress/show.html.twig', [
            'question_stress' => $questionStress,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_question_stress_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, QuestionStress $questionStress, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionStress1Type::class, $questionStress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_question_stress_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('question_stress/edit.html.twig', [
            'question_stress' => $questionStress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_stress_delete', methods: ['POST'])]
    public function delete(Request $request, QuestionStress $questionStress, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$questionStress->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($questionStress);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_question_stress_index', [], Response::HTTP_SEE_OTHER);
    }
}
