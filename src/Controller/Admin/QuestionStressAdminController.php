<?php

namespace App\Controller\Admin;

use App\Entity\QuestionStress;
use App\Form\QuestionStressType;
use App\Repository\QuestionStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/questions-stress', name: 'app_admin_question_stress_')]
class QuestionStressAdminController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(QuestionStressRepository $repo): Response
    {
        return $this->render('admin/question_stress/index.html.twig', [
            'questions' => $repo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $question = new QuestionStress();
        $form = $this->createForm(QuestionStressType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($question);
            $em->flush();

            return $this->redirectToRoute('app_admin_question_stress_index');
        }

        return $this->render('admin/question_stress/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(QuestionStress $question, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuestionStressType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_admin_question_stress_index');
        }

        return $this->render('admin/question_stress/edit.html.twig', [
            'form' => $form,
            'question' => $question,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(QuestionStress $question, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->request->get('_token'))) {
            $em->remove($question);
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_question_stress_index');
    }
}
