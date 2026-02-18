<?php

namespace App\Controller\Admin;

use App\Entity\QuestionStress;
use App\Form\QuestionStressType;
use App\Repository\QuestionStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
#[Route('/admin/question-stress')]
class AdminQuestionStressController extends AbstractController{
    #[Route(name: 'app_admin_question_stress_index', methods: ['GET'])]
    public function index(QuestionStressRepository $questionStressRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'position');
        $order = strtoupper($request->query->get('order', 'ASC'));

        $qb = $questionStressRepository->createQueryBuilder('q');

        if ($search) {
            $qb->andWhere('q.questionText LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSortFields = ['id', 'position', 'createdAt'];
        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'position';
        }
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }

        $qb->orderBy('q.' . $sort, $order);

        $questionStresses = $qb->getQuery()->getResult();

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
    public function new(Request $request, EntityManagerInterface $entityManager, QuestionStressRepository $repo): Response
    {
        $questionStress = new QuestionStress;
        $maxPosition = $repo->createQueryBuilder('q')
            ->select('MAX(q.position)')
            ->getQuery()->getSingleScalarResult() ?? 0;
        $questionStress->setPosition((int) $maxPosition + 1);

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

    #[Route('/{id}', name: 'app_admin_question_stress_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(QuestionStress $questionStress): Response
    {
        return $this->render('admin/question_stress/show.html.twig', [
            'question_stress' => $questionStress,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_question_stress_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

    #[Route('/{id}', name: 'app_admin_question_stress_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, QuestionStress $questionStress, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $questionStress->getId(), $request->request->get('_token'))) {
            $entityManager->remove($questionStress);
            $entityManager->flush();
            $this->addFlash('success', 'Question deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_question_stress_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/reorder', name: 'app_admin_question_stress_reorder', methods: ['POST'])]
    public function reorder(Request $request, EntityManagerInterface $em, QuestionStressRepository $repo): JsonResponse
    {
        $content = $request->getContent();

        $orderedIds = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($orderedIds)) {
            return new JsonResponse(['success' => false, 'message' => 'JSON invalide'], 400);
        }

        $questions = $repo->findBy(['id' => $orderedIds]);
        $map = [];
        foreach ($questions as $q) {
            $map[$q->getId()] = $q;
        }

        foreach ($orderedIds as $index => $id) {
            if (isset($map[$id])) {
                $map[$id]->setPosition($index + 1);
            }
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Ordre sauvegardé']);
    }

    #[Route('/export/pdf', name: 'app_admin_question_stress_export_pdf', methods: ['GET'])]
    public function exportPdf(QuestionStressRepository $questionStressRepository): Response
    {
        $questions = $questionStressRepository->findBy([], ['position' => 'ASC']);  // Tri par position
        
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
