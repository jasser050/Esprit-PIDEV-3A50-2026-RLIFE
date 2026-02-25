<?php

namespace App\Controller\Admin;

use App\Entity\QuestionStress;
use App\Form\QuestionStressType;
use App\Repository\QuestionStressRepository;
use App\Repository\QuizStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/questions-stress', name: 'app_admin_question_stress_')]
class QuestionStressAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(QuestionStressRepository $repo): Response
    {
        $questions = $repo->findBy([], ['position' => 'ASC']);
        return $this->render('admin/question_stress/index.html.twig', [
            'questionStresses' => $questions,
            'stats' => [
                'total' => count($questions),
                'active' => count(array_filter($questions, fn (QuestionStress $q) => (bool) $q->isIsActive())),
                'inactive' => count(array_filter($questions, fn (QuestionStress $q) => !(bool) $q->isIsActive())),
            ],
            'search' => '',
            'sort' => 'position',
            'order' => 'ASC',
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, QuestionStressRepository $repo): Response
    {
        $question = new QuestionStress();
        $maxPosition = $repo->createQueryBuilder('q')
            ->select('MAX(q.position)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $question->setPosition((int) $maxPosition + 1);

        $form = $this->createForm(QuestionStressType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $question->setCreatedAt(new \DateTime());
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Question ajoutée.');
            return $this->redirectToRoute('app_admin_question_stress_index');
        }

        return $this->render('admin/question_stress/new.html.twig', [
            'question_stress' => $question,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, QuestionStress $question, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuestionStressType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Question modifiée avec succès.');
            return $this->redirectToRoute('app_admin_question_stress_index');
        }

        return $this->render('admin/question_stress/edit.html.twig', [
            'question_stress' => $question,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, QuestionStress $question, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->request->get('_token'))) {
            $em->remove($question);
            $em->flush();
            $this->addFlash('success', 'Question supprimée.');
        }

        return $this->redirectToRoute('app_admin_question_stress_index');
    }
    #[Route('/results', name: 'results', methods: ['GET'])]
    public function results(QuizStressRepository $repo): Response
    {
        $quizzes = $repo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/quiz_stress/results.html.twig', [
            'quizzes' => $quizzes,
        ]);
    }
}
