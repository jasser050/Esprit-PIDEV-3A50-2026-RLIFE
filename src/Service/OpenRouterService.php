<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenRouterService
{
    private string $apiKey;
    private HttpClientInterface $client;

    public function __construct(
        string $openRouterApiKey,
        HttpClientInterface $client
    ) {
        $this->apiKey = $openRouterApiKey;
        $this->client = $client;
    }

    /**
     * Generate text from a plain prompt.
     */
    public function generate(string $prompt, array $options = []): ?string
    {
        return $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], $options);
    }

    /**
     * Send a complete chat payload to OpenRouter.
     *
     * @param array<int, array{role:string, content:string}> $messages
     */
    public function chat(array $messages, array $options = []): ?string
    {
        $defaultOptions = [
            'model' => 'anthropic/claude-3.5-sonnet',
            'temperature' => 0.7,
            'max_tokens' => 1200,
        ];

        $payload = array_merge($defaultOptions, $options);
        $payload['messages'] = $messages;

        try {
            $response = $this->client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'http://localhost:8000',
                    'X-Title' => 'RLIFE Wellbeing',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray(false);

            return (string) ($data['choices'][0]['message']['content'] ?? '');
        } catch (TransportExceptionInterface $e) {
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
