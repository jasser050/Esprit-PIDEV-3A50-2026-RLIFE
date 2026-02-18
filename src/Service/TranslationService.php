<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class TranslationService
{
    private const SUPPORTED_LANGUAGES = [
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'difficulty_modifier' => 0],
        'es' => ['name' => 'Español', 'flag' => '🇪🇸', 'difficulty_modifier' => 0],
        'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪', 'difficulty_modifier' => 1],
        'it' => ['name' => 'Italiano', 'flag' => '🇮🇹', 'difficulty_modifier' => 0],
        'pt' => ['name' => 'Português', 'flag' => '🇵🇹', 'difficulty_modifier' => 0],
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'difficulty_modifier' => 1],
    ];

    // Mode économique : utilise un modèle moins cher et moins de tokens
    private const USE_ECONOMY_MODE = true;
    private const ECONOMY_MODEL = 'anthropic/claude-3-haiku'; // 10x moins cher que Sonnet
    private const ECONOMY_MAX_TOKENS = 800; // Au lieu de 2000
    
    private const PREMIUM_MODEL = 'anthropic/claude-3.5-sonnet';
    private const PREMIUM_MAX_TOKENS = 2000;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $openrouterApiKey
    ) {
    }

    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    public function translateFlashcard(
        array $flashcardData,
        ?array $targetLanguages = null,
        string $sourceLang = 'fr',
        int $baseDifficulty = 2
    ): array {
        // Mode test - retourne des données simulées si pas de clé API
        if (empty($this->openrouterApiKey) || $this->openrouterApiKey === 'test_key') {
            $this->logger->warning('Mode test activé - clé API manquante ou invalide');
            return $this->getMockTranslations($flashcardData, $targetLanguages, $baseDifficulty);
        }

        if ($targetLanguages === null) {
            $targetLanguages = array_keys(self::SUPPORTED_LANGUAGES);
        }

        $results = [];

        foreach ($targetLanguages as $targetLang) {
            if (!isset(self::SUPPORTED_LANGUAGES[$targetLang])) {
                continue;
            }

            try {
                // LOG DÉTAILLÉ pour debug
                $this->logger->info("🔍 Début traduction", [
                    'target_lang' => $targetLang,
                    'source_lang' => $sourceLang,
                    'titre_length' => strlen($flashcardData['titre'] ?? ''),
                    'titre_preview' => substr($flashcardData['titre'] ?? '', 0, 30)
                ]);
                
                // Appel API réel
                $translation = $this->translateSingle($flashcardData, $sourceLang, $targetLang);
                
                // LOG du résultat
                $this->logger->info("✅ Traduction reçue", [
                    'target_lang' => $targetLang,
                    'titre_traduit' => $translation['titre'] ?? 'N/A',
                    'question_traduite' => substr($translation['question'] ?? '', 0, 50)
                ]);
                
                $adjustedDifficulty = min(5, $baseDifficulty + self::SUPPORTED_LANGUAGES[$targetLang]['difficulty_modifier']);
                
                $results[$targetLang] = [
                    'translation' => $translation,
                    'language_info' => self::SUPPORTED_LANGUAGES[$targetLang],
                    'difficulty' => $adjustedDifficulty,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $this->logger->error("Erreur traduction vers $targetLang", [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
                
                // Fallback vers mock en cas d'erreur API
                $mockResult = $this->getMockTranslations($flashcardData, [$targetLang], $baseDifficulty);
                
                $results[$targetLang] = [
                    'success' => false,
                    'error' => $this->getErrorMessage($e),
                    'translation' => $mockResult[$targetLang]['translation'] ?? null,
                    'language_info' => self::SUPPORTED_LANGUAGES[$targetLang],
                    'difficulty' => min(5, $baseDifficulty + self::SUPPORTED_LANGUAGES[$targetLang]['difficulty_modifier']),
                    'fallback_mode' => true,
                ];
            }
        }

        return $results;
    }

    private function getMockTranslations(array $data, ?array $langs, int $difficulty): array
    {
        $targets = $langs ?? array_keys(self::SUPPORTED_LANGUAGES);
        $results = [];

        foreach ($targets as $lang) {
            if (!isset(self::SUPPORTED_LANGUAGES[$lang])) continue;

            $results[$lang] = [
                'success' => true,
                'translation' => [
                    'titre' => $this->mockTranslate($data['titre'] ?? 'Titre traduit', $lang),
                    'question' => $this->mockTranslate($data['question'] ?? 'Question traduite', $lang),
                    'reponse' => $this->mockTranslate($data['reponse'] ?? 'Réponse traduite', $lang),
                    'description' => $data['description'] ? $this->mockTranslate($data['description'], $lang) : null,
                    'notes' => 'Traduction automatique (mode test/fallback)',
                ],
                'language_info' => self::SUPPORTED_LANGUAGES[$lang],
                'difficulty' => min(5, $difficulty + self::SUPPORTED_LANGUAGES[$lang]['difficulty_modifier']),
            ];
        }

        return $results;
    }

    /**
     * Traduction basique avec dictionnaire pour mode test
     */
    private function mockTranslate(string $text, string $targetLang): string
    {
        // Dictionnaire simple pour les mots courants
        $dictionaries = [
            'en' => [
                'Calcul rapide' => 'Quick calculation',
                'Comment' => 'How',
                'Réponse' => 'Answer',
                'Question' => 'Question',
                'En azert' => 'In azert',
                'simplifier' => 'simplify',
                'représenter' => 'represent',
                'addition' => 'addition',
                'sous forme de' => 'in the form of',
                'point' => 'point',
                'segment' => 'segment',
                'écriture algébrique' => 'algebraic notation',
                'Que signifie' => 'What does',
                'ce résultat' => 'this result',
                'se représente par' => 'is represented by',
            ],
            'es' => [
                'Calcul rapide' => 'Cálculo rápido',
                'Comment' => 'Cómo',
                'Réponse' => 'Respuesta',
                'Question' => 'Pregunta',
                'En azert' => 'En azert',
                'simplifier' => 'simplificar',
                'représenter' => 'representar',
            ],
            'de' => [
                'Calcul rapide' => 'Schnelle Berechnung',
                'Comment' => 'Wie',
                'Réponse' => 'Antwort',
                'Question' => 'Frage',
            ],
            'it' => [
                'Calcul rapide' => 'Calcolo rapido',
                'Comment' => 'Come',
                'Réponse' => 'Risposta',
                'Question' => 'Domanda',
            ],
            'pt' => [
                'Calcul rapide' => 'Cálculo rápido',
                'Comment' => 'Como',
                'Réponse' => 'Resposta',
                'Question' => 'Pergunta',
            ],
            'ar' => [
                'Calcul rapide' => 'حساب سريع',
                'Comment' => 'كيف',
                'Réponse' => 'إجابة',
                'Question' => 'سؤال',
            ],
        ];

        $dict = $dictionaries[$targetLang] ?? [];
        
        // Appliquer les traductions du dictionnaire
        $translated = $text;
        foreach ($dict as $fr => $target) {
            $translated = str_ireplace($fr, $target, $translated);
        }
        
        return $translated;
    }

    private function translateSingle(array $data, string $sourceLang, string $targetLang): array
    {
        $langInfo = self::SUPPORTED_LANGUAGES[$targetLang];
        
        // Choisir le modèle et les tokens selon le mode
        $model = self::USE_ECONOMY_MODE ? self::ECONOMY_MODEL : self::PREMIUM_MODEL;
        $maxTokens = self::USE_ECONOMY_MODE ? self::ECONOMY_MAX_TOKENS : self::PREMIUM_MAX_TOKENS;
        
        // Construction du prompt pour l'IA (version compacte pour économiser des tokens)
        $prompt = $this->buildCompactPrompt($data, $sourceLang, $targetLang, $langInfo['name']);
        
        try {
            $this->logger->info("Traduction via OpenRouter", [
                'target_lang' => $targetLang,
                'model' => $model,
                'max_tokens' => $maxTokens,
                'economy_mode' => self::USE_ECONOMY_MODE
            ]);

            $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openrouterApiKey,
                    'HTTP-Referer' => 'https://flashcard-app.com',
                    'X-Title' => 'Flashcard Translation Service',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => $maxTokens,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                throw new \Exception("HTTP $statusCode - " . $response->getContent(false));
            }

            $result = $response->toArray();
            
            if (!isset($result['choices'][0]['message']['content'])) {
                throw new \Exception('Réponse API invalide - contenu manquant');
            }

            $translatedText = $result['choices'][0]['message']['content'];
            
            $this->logger->info("Traduction réussie", [
                'target_lang' => $targetLang,
                'length' => strlen($translatedText),
                'tokens_used' => $result['usage']['total_tokens'] ?? 'unknown'
            ]);
            
            // Parser la réponse JSON de l'IA
            return $this->parseTranslationResponse($translatedText, $data);
            
        } catch (HttpExceptionInterface $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $errorBody = $e->getResponse()->getContent(false);
            
            $this->logger->error("Erreur HTTP API OpenRouter", [
                'status_code' => $statusCode,
                'error_body' => $errorBody,
                'target_lang' => $targetLang
            ]);
            
            throw new \Exception($this->formatApiError($statusCode, $errorBody), $statusCode);
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur générale traduction", [
                'error' => $e->getMessage(),
                'target_lang' => $targetLang
            ]);
            throw $e;
        }
    }

    /**
     * Prompt compact pour économiser des tokens
     */
    private function buildCompactPrompt(array $data, string $sourceLang, string $targetLang, string $targetLangName): string
    {
        $sourceNames = [
            'fr' => 'French',
            'en' => 'English',
            'es' => 'Spanish',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ar' => 'Arabic',
        ];

        $sourceName = $sourceNames[$sourceLang] ?? $sourceLang;

        // Version ultra-compacte du prompt pour économiser des tokens
        return sprintf(
            "Translate this flashcard from %s to %s. Keep Markdown formatting. Return ONLY valid JSON:\n\n" .
            "Title: %s\nQuestion: %s\nAnswer: %s\nDescription: %s\n\n" .
            "JSON format:\n{\"titre\":\"...\",\"question\":\"...\",\"reponse\":\"...\",\"description\":\"...\",\"notes\":\"...\"}",
            $sourceName,
            $targetLangName,
            $data['titre'],
            $data['question'],
            $data['reponse'],
            $data['description'] ?? 'null'
        );
    }

    private function parseTranslationResponse(string $response, array $fallbackData): array
    {
        // Nettoyer la réponse (enlever les blocs de code markdown si présents)
        $cleaned = preg_replace('/```json\s*|\s*```/', '', trim($response));
        
        try {
            $parsed = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);
            
            return [
                'titre' => $parsed['titre'] ?? $fallbackData['titre'],
                'question' => $parsed['question'] ?? $fallbackData['question'],
                'reponse' => $parsed['reponse'] ?? $fallbackData['reponse'],
                'description' => $parsed['description'] ?? $fallbackData['description'],
                'notes' => $parsed['notes'] ?? null,
            ];
            
        } catch (\JsonException $e) {
            $this->logger->error("Erreur parsing JSON de traduction", [
                'error' => $e->getMessage(),
                'response_preview' => substr($response, 0, 200)
            ]);
            
            // Fallback : retourner les données originales
            return [
                'titre' => $fallbackData['titre'],
                'question' => $fallbackData['question'],
                'reponse' => $fallbackData['reponse'],
                'description' => $fallbackData['description'],
                'notes' => 'Erreur de traduction - texte original conservé',
            ];
        }
    }

    /**
     * Formater les erreurs API de manière compréhensible
     */
    private function formatApiError(int $statusCode, string $errorBody): string
    {
        $errors = [
            400 => 'Requête invalide - vérifiez le format des données',
            401 => 'Clé API invalide ou manquante',
            402 => 'Crédit insuffisant - Ajoutez du crédit sur OpenRouter',
            403 => 'Accès refusé - vérifiez vos permissions',
            429 => 'Limite de requêtes atteinte - attendez quelques secondes',
            500 => 'Erreur serveur OpenRouter - réessayez plus tard',
            503 => 'Service temporairement indisponible',
        ];

        $message = $errors[$statusCode] ?? "Erreur API (code $statusCode)";
        
        // Essayer d'extraire plus d'infos du body
        try {
            $body = json_decode($errorBody, true);
            if (isset($body['error']['message'])) {
                $detailMessage = $body['error']['message'];
                
                // Si c'est une erreur de tokens, donner plus d'infos
                if (strpos($detailMessage, 'max_tokens') !== false) {
                    $message = 'Crédit insuffisant pour cette requête. Solutions : 1) Ajoutez $5 sur OpenRouter, ou 2) Utilisez le mode économique (Claude Haiku au lieu de Sonnet)';
                } else {
                    $message .= ' - ' . $detailMessage;
                }
            }
        } catch (\Exception $e) {
            // Ignorer si le body n'est pas du JSON
        }
        
        return $message;
    }

    /**
     * Obtenir un message d'erreur convivial
     */
    private function getErrorMessage(\Exception $e): string
    {
        $code = $e->getCode();
        
        if ($code === 402) {
            return 'Crédit insuffisant. Ajoutez au moins $5 sur https://openrouter.ai/settings/credits';
        }
        
        if ($code === 401) {
            return 'Clé API invalide. Vérifiez votre configuration OPENROUTER_API_KEY';
        }
        
        return $e->getMessage();
    }
}