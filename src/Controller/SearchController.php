<?php

namespace App\Controller;

use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/api/test', name: 'api_test', methods: ['GET'])]
    public function test(SearchService $searchService): JsonResponse
    {
        try {
            $results = $searchService->search('test', 3);

            return $this->json([
                'success' => true,
                'message' => 'SerpAPI fonctionne correctement !',
                'sample_results' => count($results['organic_results']),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request, SearchService $searchService): JsonResponse
    {
        $query = $request->query->get('q', '');
        $numResults = (int) $request->query->get('num', 10);

        if (empty($query)) {
            return $this->json([
                'success' => false,
                'error' => 'Le paramètre "q" (query) est requis',
            ], 400);
        }

        if ($numResults < 1 || $numResults > 100) {
            return $this->json([
                'success' => false,
                'error' => 'Le paramètre "num" doit être entre 1 et 100',
            ], 400);
        }

        try {
            $results = $searchService->search($query, $numResults);

            return $this->json([
                'success' => true,
                'query' => $query,
                'total_results' => count($results['organic_results']),
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/search/courses', name: 'api_search_courses', methods: ['GET'])]
    public function searchCourses(Request $request, SearchService $searchService): JsonResponse
    {
        $query = $request->query->get('q', '');
        $numResults = (int) $request->query->get('num', 10);
        $filterPlatforms = $request->query->get('filter', 'false') === 'true';

        if (empty($query)) {
            return $this->json([
                'success' => false,
                'error' => 'Le paramètre "q" (query) est requis',
            ], 400);
        }

        try {
            $results = $searchService->searchCourses($query, $numResults);

            if ($filterPlatforms) {
                $results = $searchService->filterCoursePlatforms($results);
            }

            return $this->json([
                'success' => true,
                'query' => $query,
                'total_results' => count($results['organic_results']),
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}