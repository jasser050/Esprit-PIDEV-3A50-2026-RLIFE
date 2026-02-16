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
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/question-stress')]
class AdminQuestionStressController extends AbstractController
{
    #[Route(name: 'app_admin_question_stress_index', methods: ['GET'])]
    public function index(QuestionStressRepository $questionStressRepository, Request $request): Response
    {
        // Search functionality
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'ASC');
        
        $qb = $questionStressRepository->createQueryBuilder('q');
        
        if ($search) {
            $qb->andWhere('q.questionText LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Validate sort field
        $allowedSortFields = ['id', 'questionNumber', 'createdAt'];
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'id';
        }
        
        $qb->orderBy('q.' . $sort, $order);
        
        $questionStresses = $qb->getQuery()->getResult();
        
        // Statistics
        $stats = [
            'total' => count($questionStresses),
            'active' => count(array_filter($questionStresses, fn($q) => $q->isIsActive())),
            'inactive' => count(array_filter($questionStresses, fn($q) => !$q->isIsActive())),
        ];

        return $this->render('admin/question_stress/index.html.twig', [
            'questionStresses' => $questionStresses,
            'stats' => $stats,
            'search' => $search,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'app_admin_question_stress_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $questionStress = new QuestionStress();
        $form = $this->createForm(QuestionStressType::class, $questionStress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $questionStress->setCreatedAt(new \DateTime());
            $entityManager->persist($questionStress);
            $entityManager->flush();
            
            $this->addFlash('success', 'Question created successfully!');

            return $this->redirectToRoute('app_admin_question_stress_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/question_stress/new.html.twig', [
            'question_stress' => $questionStress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_question_stress_show', methods: ['GET'])]
    public function show(QuestionStress $questionStress): Response
    {
        return $this->render('admin/question_stress/show.html.twig', [
            'question_stress' => $questionStress,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_question_stress_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, QuestionStress $questionStress, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionStressType::class, $questionStress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $questionStress->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            
            $this->addFlash('success', 'Question updated successfully!');

            return $this->redirectToRoute('app_admin_question_stress_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/question_stress/edit.html.twig', [
            'question_stress' => $questionStress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_question_stress_delete', methods: ['POST'])]
    public function delete(Request $request, QuestionStress $questionStress, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$questionStress->getId(), $request->request->get('_token'))) {
            $entityManager->remove($questionStress);
            $entityManager->flush();
            $this->addFlash('success', 'Question deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_question_stress_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/export/pdf', name: 'app_admin_question_stress_export_pdf', methods: ['GET'])]
    public function exportPdf(QuestionStressRepository $questionStressRepository): Response
    {
        $questions = $questionStressRepository->findAll();
        
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        
        $html = $this->renderView('admin/question_stress/pdf.html.twig', [
            'questions' => $questions,
            'exportDate' => new \DateTime(),
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="stress_questions.pdf"',
            ]
        );
    }
}