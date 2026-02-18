<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for searching courses and resources using Google Custom Search API
 * 
 * To use this service, you need to:
 * 1. Create a Google Custom Search Engine at: https://programmablesearchengine.google.com/
 * 2. Get an API key from: https://console.cloud.google.com/apis/credentials
 * 3. Add these to your .env file:
 *    GOOGLE_SEARCH_API_KEY=your_api_key
 *    GOOGLE_SEARCH_ENGINE_ID=your_search_engine_id
 */
class GoogleSearchService
{
    private HttpClientInterface $httpClient;
    private ?string $apiKey;
    private ?string $searchEngineId;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        // Get from environment variables
        $this->apiKey = $_ENV['GOOGLE_SEARCH_API_KEY'] ?? null;
        $this->searchEngineId = $_ENV['GOOGLE_SEARCH_ENGINE_ID'] ?? null;
    }

    /**
     * Check if the service is properly configured
     */
    private function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->searchEngineId);
    }

    /**
     * Search for courses on a given topic
     * 
     * @param string $query - Search query (e.g., "Mathematics")
     * @param int $limit - Number of results (max 10 per request)
     * @return array - Array of search results
     */
    public function searchCourses(string $query, int $limit = 5): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/customsearch/v1', [
                'query' => [
                    'key' => $this->apiKey,
                    'cx' => $this->searchEngineId,
                    'q' => "cours {$query} online course tutorial",
                    'num' => min($limit, 10),
                ],
            ]);

            $data = $response->toArray();
            return $data['items'] ?? [];
        } catch (\Exception $e) {
            // Log error but don't throw - gracefully return empty array
            error_log("GoogleSearchService error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get search information (total results count)
     * 
     * @param string $query - Search query
     * @return array - Contains 'totalResults', 'searchTime', etc.
     */
    public function getSearchInfo(string $query): array
    {
        if (!$this->isConfigured()) {
            return ['totalResults' => 0];
        }

        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/customsearch/v1', [
                'query' => [
                    'key' => $this->apiKey,
                    'cx' => $this->searchEngineId,
                    'q' => $query,
                    'num' => 1, // We just want the search info
                ],
            ]);

            $data = $response->toArray();
            return $data['searchInformation'] ?? ['totalResults' => 0];
        } catch (\Exception $e) {
            error_log("GoogleSearchService error: " . $e->getMessage());
            return ['totalResults' => 0];
        }
    }

    /**
     * Search on specific sites
     * 
     * @param string $query - Search query
     * @param array $sites - Array of site domains (e.g., ['coursera.org', 'edx.org'])
     * @return array - Search results
     */
    public function searchOnSites(string $query, array $sites): array
    {
        if (!$this->isConfigured() || empty($sites)) {
            return [];
        }

        try {
            // Build site: query (e.g., "site:coursera.org OR site:edx.org")
            $siteQuery = implode(' OR ', array_map(fn($site) => "site:{$site}", $sites));
            $fullQuery = "{$query} ({$siteQuery})";

            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/customsearch/v1', [
                'query' => [
                    'key' => $this->apiKey,
                    'cx' => $this->searchEngineId,
                    'q' => $fullQuery,
                    'num' => 10,
                ],
            ]);

            $data = $response->toArray();
            return $data['items'] ?? [];
        } catch (\Exception $e) {
            error_log("GoogleSearchService error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search with custom filters
     * 
     * @param string $query - Search query
     * @param array $filters - Filters like ['fileType' => 'pdf', 'num' => 3]
     * @return array - Filtered search results
     */
    public function searchWithFilters(string $query, array $filters = []): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $params = [
                'key' => $this->apiKey,
                'cx' => $this->searchEngineId,
                'q' => $query,
                'num' => $filters['num'] ?? 10,
            ];

            // Add fileType filter if specified
            if (isset($filters['fileType'])) {
                $params['fileType'] = $filters['fileType'];
            }

            // Add date restrict if specified
            if (isset($filters['dateRestrict'])) {
                $params['dateRestrict'] = $filters['dateRestrict'];
            }

            // Add language if specified
            if (isset($filters['lr'])) {
                $params['lr'] = $filters['lr'];
            }

            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/customsearch/v1', [
                'query' => $params,
            ]);

            $data = $response->toArray();
            return $data['items'] ?? [];
        } catch (\Exception $e) {
            error_log("GoogleSearchService error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search for video tutorials
     * 
     * @param string $query - Search query
     * @param int $limit - Number of results
     * @return array - Video search results
     */
    public function searchVideos(string $query, int $limit = 5): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            // Search on YouTube and other video sites
            $videoSites = ['youtube.com', 'vimeo.com', 'dailymotion.com'];
            $siteQuery = implode(' OR ', array_map(fn($site) => "site:{$site}", $videoSites));
            $fullQuery = "{$query} tutorial ({$siteQuery})";

            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/customsearch/v1', [
                'query' => [
                    'key' => $this->apiKey,
                    'cx' => $this->searchEngineId,
                    'q' => $fullQuery,
                    'num' => min($limit, 10),
                ],
            ]);

            $data = $response->toArray();
            return $data['items'] ?? [];
        } catch (\Exception $e) {
            error_log("GoogleSearchService error: " . $e->getMessage());
            return [];
        }
    }
}
