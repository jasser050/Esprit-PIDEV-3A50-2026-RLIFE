<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeminiService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $geminiApiKey
    ) {}

    /**
     * Send a chat message to Gemini AI
     *
     * @param array $messages Array of messages with 'role' and 'content'
     * @return string|null The AI response or null on error
     */
    public function chat(array $messages): ?string
    {
        // Log API key status (first 10 chars only for security)
        $keyPreview = substr($this->geminiApiKey, 0, 10) . '...';
        $this->logger->info('Gemini API: Attempting to call API', ['keyPreview' => $keyPreview]);

        try {
            // Convert messages to Gemini format
            $contents = $this->convertMessagesToGeminiFormat($messages);

            $this->logger->info('Gemini API: Sending request', ['messageCount' => count($contents)]);

            $response = $this->httpClient->request('POST', self::API_URL . '?key=' . $this->geminiApiKey, [
                'json' => [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 1024,
                    ],
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('Gemini API: Response received', ['statusCode' => $statusCode]);

            $data = $response->toArray(false);

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            // Log full response for debugging
            $this->logger->error('Gemini API: Unexpected response format', [
                'data' => json_encode($data),
                'statusCode' => $statusCode
            ]);
            
            // If there's an error in the response, throw it
            if (isset($data['error'])) {
                throw new \Exception('Gemini API Error: ' . ($data['error']['message'] ?? 'Unknown error'));
            }
            
            return null;

        } catch (\Throwable $e) {
            $this->logger->error('Gemini API error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw for better error visibility
            throw $e;
        }
    }

    /**
     * Convert OpenAI-style messages to Gemini format
     */
    private function convertMessagesToGeminiFormat(array $messages): array
    {
        $contents = [];
        
        foreach ($messages as $message) {
            $role = $message['role'];
            
            // Gemini uses 'user' and 'model' (not 'assistant')
            if ($role === 'assistant') {
                $role = 'model';
            } elseif ($role === 'system') {
                // Gemini doesn't have system role, merge with first user message
                $role = 'user';
            }

            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $message['content']]
                ]
            ];
        }

        return $contents;
    }
}
