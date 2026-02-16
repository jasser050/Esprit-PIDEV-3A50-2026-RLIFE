<?php

namespace App\Controller\Api;

use App\Entity\RecommendationStress;
use App\Repository\RecommendationStressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/wellbeing/recommendations', name: 'api_wellbeing_recommendations_')]
class WellbeingRecommendationsApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(RecommendationStressRepository $repo): JsonResponse
    {
        $recommendations = $repo->findAllActive();
        
        $data = array_map(function (RecommendationStress $rec) {
            return [
                'id' => $rec->getId(),
                'title' => $rec->getTitle(),
                'content' => $rec->getContent(),
                'level' => $rec->getLevel(),
            ];
        }, $recommendations);

        return $this->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    #[Route('/by-level/{level}', name: 'by_level', methods: ['GET'])]
    public function byLevel(string $level, RecommendationStressRepository $repo): JsonResponse
    {
        $validLevels = ['low', 'medium', 'high'];
        $level = strtolower($level);
        
        if (!in_array($level, $validLevels)) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid level. Use: low, medium, or high',
            ], 400);
        }

        $recommendations = $repo->findByLevel($level);
        
        $data = array_map(function (RecommendationStress $rec) {
            return [
                'id' => $rec->getId(),
                'title' => $rec->getTitle(),
                'content' => $rec->getContent(),
                'level' => $rec->getLevel(),
            ];
        }, $recommendations);

        return $this->json([
            'success' => true,
            'level' => $level,
            'count' => count($data),
            'data' => $data,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(RecommendationStress $rec): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $rec->getId(),
                'title' => $rec->getTitle(),
                'content' => $rec->getContent(),
                'level' => $rec->getLevel(),
                'isActive' => $rec->isIsActive(),
                'createdAt' => $rec->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
