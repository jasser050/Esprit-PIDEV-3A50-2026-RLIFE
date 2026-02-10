<?php

namespace App\Controller\Admin;

use App\Entity\QuizStress;
use App\Form\QuizStressType;
use App\Repository\QuizStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/quizzes-stress', name: 'app_admin_quiz_stress_')]
class QuizStressAdminController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(QuizStressRepository $repo): Response
    {
        return $this->render('admin/quiz_stress/index.html.twig', [
            'quizzes' => $repo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $quiz = new QuizStress();
        $form = $this->createForm(QuizStressType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($quiz);
            $em->flush();
            return $this->redirectToRoute('app_admin_quiz_stress_index');
        }

        return $this->render('admin/quiz_stress/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(QuizStress $quiz, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuizStressType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_admin_quiz_stress_index');
        }

        return $this->render('admin/quiz_stress/edit.html.twig', [
            'form' => $form,
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(QuizStress $quiz, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_quiz_stress_index');
    }
}
