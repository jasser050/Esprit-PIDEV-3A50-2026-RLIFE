<?php

namespace App\Controller\Admin;

use App\Entity\QuizStress;
use App\Entity\QuestionStress;
use App\Form\QuizStressType;
use App\Form\QuestionStressType;
use App\Repository\QuizStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/quizzes-stress', name: 'app_admin_quiz_stress_')]
class QuizStressAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(QuizStressRepository $quizRepository): Response
    {
        return $this->render('admin/quiz_stress/index.html.twig', [
            'quizzes' => $quizRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $quiz = new QuizStress();
        $form = $this->createForm(QuizStressType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($quiz);
            $em->flush();

            $this->addFlash('success', 'Quiz créé avec succès.');
            return $this->redirectToRoute('app_admin_quiz_stress_index');
        }

        return $this->render('admin/quiz_stress/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, QuizStress $quiz, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuizStressType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Quiz modifié avec succès.');
            return $this->redirectToRoute('app_admin_quiz_stress_index');
        }

        return $this->render('admin/quiz_stress/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/add-question', name: 'add_question', methods: ['GET', 'POST'])]
    public function addQuestion(Request $request, QuizStress $quiz, EntityManagerInterface $em): Response
    {
        $question = new QuestionStress();
        $question->setQuiz($quiz); // Liaison automatique

        $form = $this->createForm(QuestionStressType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($question);
            $em->flush();

            $this->addFlash('success', 'Question ajoutée avec succès.');
            return $this->redirectToRoute('app_admin_quiz_stress_edit', ['id' => $quiz->getId()]);
        }

        return $this->render('admin/quiz_stress/add_question.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, QuizStress $quiz, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();

            $this->addFlash('success', 'Quiz supprimé.');
        }

        return $this->redirectToRoute('app_admin_quiz_stress_index');
    }
}