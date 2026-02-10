<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
     * Génère du texte via OpenRouter (ex: questions QCM)
     */
    public function generate(string $prompt, array $options = []): ?string
    {
        $defaultOptions = [
            'model' => 'anthropic/claude-3.5-sonnet',     // ou google/gemini-flash-1.5, openai/gpt-4o-mini, etc.
            'temperature' => 0.7,
            'max_tokens' => 1500,
        ];

        $payload = array_merge($defaultOptions, $options);
        $payload['messages'] = [
            ['role' => 'user', 'content' => $prompt]
        ];

        try {
            $response = $this->client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'https://ton-site.com',           // obligatoire selon OpenRouter
                    'X-Title' => 'RLIFE Quiz Generator',                 // optionnel mais conseillé
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray();

            return $data['choices'][0]['message']['content'] ?? null;

        } catch (TransportExceptionInterface $e) {
            // Log l'erreur si tu as un logger
            return null;
        }
    }
}