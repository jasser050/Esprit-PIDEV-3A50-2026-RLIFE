<?php

namespace App\Controller;

use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StudyAssistantController extends AbstractController
{
    #[Route('/assistant', name: 'app_study_assistant')]
    public function index(): Response
    {
        return $this->render('pages/courses/assistant.html.twig');
    }

    #[Route('/api/assistant/suggest', name: 'api_assistant_suggest', methods: ['GET'])]
    public function suggest(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json(['suggestions' => []]);
        }

        $suggestions = [
            'Mathematics' => ['integral calculus', 'linear algebra', 'probability', 'statistics'],
            'Computer Science' => ['python', 'java', 'database', 'algorithms', 'web development'],
            'Physics' => ['mechanics', 'electricity', 'optics', 'thermodynamics'],
            'Chemistry' => ['organic chemistry', 'inorganic chemistry', 'chemical reactions'],
            'English' => ['english grammar', 'vocabulary', 'writing skills', 'TOEFL'],
            'Business' => ['marketing', 'finance', 'management', 'economics'],
            'Design' => ['UI/UX design', 'graphic design', 'web design', 'photoshop'],
        ];

        $results = [];
        
        foreach ($suggestions as $category => $items) {
            foreach ($items as $item) {
                if (stripos($item, $query) !== false || stripos($category, $query) !== false) {
                    $results[] = [
                        'text' => $item,
                        'category' => $category,
                        'icon' => $this->getCategoryIcon($category)
                    ];
                }
            }
        }

        return $this->json(['suggestions' => array_slice($results, 0, 5)]);
    }

    #[Route('/api/assistant/search', name: 'api_assistant_search', methods: ['POST'])]
    public function search(Request $request, SearchService $searchService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $query = $data['query'] ?? '';
        $subject = $data['subject'] ?? '';

        if (empty($query)) {
            return $this->json(['success' => false, 'error' => 'Query is required'], 400);
        }

        try {
            $results = $searchService->searchCourses($query, 8);
            $results = $searchService->filterCoursePlatforms($results);

            $tips = $this->generateStudyTips($subject, $query);

            return $this->json([
                'success' => true,
                'query' => $query,
                'courses' => $results['organic_results'] ?? [],
                'tips' => $tips,
                'total' => count($results['organic_results'] ?? [])
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getCategoryIcon(string $category): string
    {
        $icons = [
            'Mathematics' => '🔢',
            'Computer Science' => '💻',
            'Physics' => '⚛️',
            'Chemistry' => '🧪',
            'English' => '🇬🇧',
            'Business' => '💼',
            'Design' => '🎨',
        ];

        return $icons[$category] ?? '📚';
    }

    private function generateStudyTips(string $subject, string $query): array
    {
        return [
            [
                'icon' => '📖',
                'title' => 'Start with the basics',
                'description' => "Make sure you master the fundamental concepts of {$query} before moving on to advanced topics."
            ],
            [
                'icon' => '✍️',
                'title' => 'Practice regularly',
                'description' => "Do daily exercises on {$query} to reinforce your knowledge."
            ],
            [
                'icon' => '👥',
                'title' => 'Study in groups',
                'description' => "Join or create a study group to discuss and deepen your understanding of {$query}."
            ],
            [
                'icon' => '🎯',
                'title' => 'Set clear goals',
                'description' => "Define weekly goals to track your progress in {$query}."
            ],
        ];
    }
}