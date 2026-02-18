<?php

namespace App\Controller\Admin;

use App\Entity\RecommendationStress;
use App\Form\RecommendationStressType;
use App\Repository\RecommendationStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/recommendations-stress', name: 'app_admin_recommendation_stress_')]
class RecommendationStressAdminController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(RecommendationStressRepository $repo, Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'createdAt');
        $order = strtoupper((string) $request->query->get('order', 'DESC'));

        $qb = $repo->createQueryBuilder('r');

        if ($search !== '') {
            $qb->andWhere('r.title LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $allowedSortFields = ['id', 'title', 'createdAt'];
        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'createdAt';
        }

        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $qb->orderBy('r.' . $sort, $order);

        $recommendations = $qb->getQuery()->getResult();

        $stats = [
            'total' => count($recommendations),
            'low' => count(array_filter($recommendations, fn($r) => $r->getLevel() === 'low')),
            'medium' => count(array_filter($recommendations, fn($r) => $r->getLevel() === 'medium')),
            'high' => count(array_filter($recommendations, fn($r) => $r->getLevel() === 'high')),
        ];

        return $this->render('admin/recommendation_stress/index.html.twig', [
            'recommendations' => $recommendations,
            'stats' => $stats,
            'search' => $search,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $recommendation = new RecommendationStress();
        $form = $this->createForm(RecommendationStressType::class, $recommendation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($recommendation);
            $em->flush();

            $this->addFlash('success', 'Recommendation created successfully!');

            return $this->redirectToRoute('app_admin_recommendation_stress_index');
        }

        return $this->render('admin/recommendation_stress/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(RecommendationStress $recommendation): Response
    {
        return $this->render('admin/recommendation_stress/show.html.twig', [
            'recommendation' => $recommendation,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(RecommendationStress $recommendation, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RecommendationStressType::class, $recommendation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recommendation->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Recommendation updated successfully!');

            return $this->redirectToRoute('app_admin_recommendation_stress_index');
        }

        return $this->render('admin/recommendation_stress/edit.html.twig', [
            'form' => $form,
            'recommendation' => $recommendation,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(RecommendationStress $recommendation, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $recommendation->getId(), $request->request->get('_token'))) {
            $em->remove($recommendation);
            $em->flush();
            $this->addFlash('success', 'Recommendation deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_recommendation_stress_index');
    }
}
