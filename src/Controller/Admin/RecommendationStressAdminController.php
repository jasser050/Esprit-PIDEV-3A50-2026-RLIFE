<?php

namespace App\Controller\Admin;

use App\Entity\RecommendationStress;
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
    public function index(RecommendationStressRepository $repo): Response
    {
        return $this->render('admin/recommendation_stress/index.html.twig', [
            'items' => $repo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(RecommendationStress $rec, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rec->getId(), $request->request->get('_token'))) {
            $em->remove($rec);
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_recommendation_stress_index');
    }
}
