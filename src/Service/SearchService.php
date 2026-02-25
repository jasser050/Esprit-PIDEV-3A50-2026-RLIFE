<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchService
{
    private string $apiKey;
    private string $baseUrl = 'https://serpapi.com/search.json';

    public function __construct(ParameterBagInterface $params)
    {
        $this->apiKey = $params->get('app.serpapi_key');
        
        if (empty($this->apiKey)) {
            throw new Exception('SERPAPI_KEY n\'est pas configurée');
        }
    }

    /**
     * Recherche des cours en ligne
     */
    public function searchCourses(string $query, int $numResults = 10): array
    {
        $enhancedQuery = $query . ' course mooc online';
        
        $params = [
            'engine' => 'google',
            'q' => $enhancedQuery,
            'api_key' => $this->apiKey,
            'num' => min($numResults, 100),
            'hl' => 'fr',
            'gl' => 'tn',
        ];

        $url = $this->baseUrl . '?' . http_build_query($params);

        try {
            $response = $this->makeRequest($url);
            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON : ' . json_last_error_msg());
            }

            return $this->formatResults($data);
        } catch (Exception $e) {
            throw new Exception('Erreur lors de la recherche : ' . $e->getMessage());
        }
    }

    /**
     * Recherche simple
     */
    public function search(string $query, int $numResults = 10): array
    {
        $params = [
            'engine' => 'google',
            'q' => $query,
            'api_key' => $this->apiKey,
            'num' => min($numResults, 100),
        ];

        $url = $this->baseUrl . '?' . http_build_query($params);

        try {
            $response = $this->makeRequest($url);
            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur de décodage JSON : ' . json_last_error_msg());
            }

            return $this->formatResults($data);
        } catch (Exception $e) {
            throw new Exception('Erreur lors de la recherche : ' . $e->getMessage());
        }
    }

    private function makeRequest(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: StudyFlow/1.0',
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception('Impossible de contacter l\'API SerpAPI');
        }

        return $response;
    }

    private function formatResults(array $data): array
    {
        $results = [
            'search_metadata' => [
                'status' => $data['search_metadata']['status'] ?? 'unknown',
                'created_at' => $data['search_metadata']['created_at'] ?? null,
                'total_time_taken' => $data['search_metadata']['total_time_taken'] ?? null,
            ],
            'search_information' => [
                'query' => $data['search_parameters']['q'] ?? '',
                'total_results' => $data['search_information']['total_results'] ?? 0,
            ],
            'organic_results' => [],
            'related_questions' => [],
        ];

        if (isset($data['organic_results']) && is_array($data['organic_results'])) {
            foreach ($data['organic_results'] as $result) {
                $results['organic_results'][] = [
                    'position' => $result['position'] ?? 0,
                    'title' => $result['title'] ?? '',
                    'link' => $result['link'] ?? '',
                    'displayed_link' => $result['displayed_link'] ?? '',
                    'snippet' => $result['snippet'] ?? '',
                    'source' => $result['source'] ?? '',
                    'thumbnail' => $result['thumbnail'] ?? null,
                ];
            }
        }

        if (isset($data['related_questions']) && is_array($data['related_questions'])) {
            foreach ($data['related_questions'] as $question) {
                $results['related_questions'][] = [
                    'question' => $question['question'] ?? '',
                    'snippet' => $question['snippet'] ?? '',
                    'title' => $question['title'] ?? '',
                    'link' => $question['link'] ?? '',
                ];
            }
        }

        return $results;
    }

    public function filterCoursePlatforms(array $results): array
    {
        $coursePlatforms = [
            'udemy.com',
            'coursera.org',
            'edx.org',
            'linkedin.com/learning',
            'pluralsight.com',
            'skillshare.com',
            'codecademy.com',
            'khanacademy.org',
            'udacity.com',
            'futurelearn.com',
            'openclassrooms.com',
            'youtube.com',
        ];

        $filtered = [];

        foreach ($results['organic_results'] as $result) {
            foreach ($coursePlatforms as $platform) {
                if (stripos($result['link'], $platform) !== false) {
                    $filtered[] = $result;
                    break;
                }
            }
        }

        $results['organic_results'] = $filtered;
        return $results;
    }
}