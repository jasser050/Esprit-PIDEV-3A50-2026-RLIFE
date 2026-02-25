<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for generating AI-powered wellbeing recommendations
 * Uses OpenAI API to provide personalized stress management advice
 */
class WellbeingAiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
    }
    
    /**
     * Generate personalized stress management recommendations
     * 
     * @param int $stressLevel Current stress level (0-10)
     * @param string $mood Current mood
     * @param array $recentCheckins Array of recent wellbeing check-ins
     * @return array Array of personalized recommendations
     */
    public function generateRecommendations(int $stressLevel, string $mood, array $recentCheckins = []): array
    {
        if (empty($this->apiKey)) {
            // Fallback to default recommendations if API key not configured
            return $this->getDefaultRecommendations($stressLevel, $mood);
        }
        
        try {
            $prompt = $this->buildPrompt($stressLevel, $mood, $recentCheckins);
            
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a compassionate mental health assistant. Provide practical, evidence-based stress management recommendations for students. Keep responses concise and actionable.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ],
            ]);
            
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            
            return $this->parseRecommendations($content);
            
        } catch (\Exception $e) {
            // Log error and return default recommendations
            error_log('Wellbeing AI Service error: ' . $e->getMessage());
            return $this->getDefaultRecommendations($stressLevel, $mood);
        }
    }
    
    /**
     * Build prompt for AI based on user data
     */
    private function buildPrompt(int $stressLevel, string $mood, array $recentCheckins): string
    {
        $prompt = "Current stress level: {$stressLevel}/10\n";
        $prompt .= "Current mood: {$mood}\n";
        
        if (!empty($recentCheckins)) {
            $prompt .= "\nRecent check-ins:\n";
            foreach (array_slice($recentCheckins, 0, 5) as $checkin) {
                $date = $checkin->getEntryDateWell()->format('M j');
                $stress = $checkin->getStressLevelWell();
                $energy = $checkin->getEnergyLevelWell();
                $sleep = $checkin->getSleepHoursWell();
                $prompt .= "- {$date}: Stress {$stress}/10, Energy {$energy}/10, Sleep {$sleep}h\n";
            }
        }
        
        $prompt .= "\nBased on this data, provide 3 specific, actionable recommendations for managing stress and improving wellbeing. Format as JSON with 'title', 'description', and 'category' for each recommendation. Categories should be: breathing, exercise, sleep, mindfulness, or study_habits.";
        
        return $prompt;
    }
    
    /**
     * Parse AI response into structured recommendations
     */
    private function parseRecommendations(string $content): array
    {
        // Try to extract JSON from response
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json['recommendations'])) {
                return $json['recommendations'];
            }
        }
        
        // Fallback: parse as text
        $lines = explode("\n", $content);
        $recommendations = [];
        $currentRec = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/^(\d+)[\.\)]\s*(.+)/', $line, $matches)) {
                if ($currentRec) {
                    $recommendations[] = $currentRec;
                }
                $currentRec = [
                    'title' => $matches[2],
                    'description' => '',
                    'category' => 'mindfulness',
                ];
            } elseif ($currentRec) {
                $currentRec['description'] .= $line . ' ';
            }
        }
        
        if ($currentRec) {
            $recommendations[] = $currentRec;
        }
        
        return $recommendations ?: $this->getDefaultRecommendations(5, 'okay');
    }
    
    /**
     * Get default recommendations when AI is unavailable
     */
    private function getDefaultRecommendations(int $stressLevel, string $mood): array
    {
        $recommendations = [];
        
        if ($stressLevel >= 7) {
            $recommendations[] = [
                'title' => '4-7-8 Breathing Exercise',
                'description' => 'Inhale for 4 counts, hold for 7, exhale for 8. Repeat 4 times to activate your parasympathetic nervous system.',
                'category' => 'breathing',
            ];
            $recommendations[] = [
                'title' => 'Progressive Muscle Relaxation',
                'description' => 'Tense and relax each muscle group from toes to head. This releases physical tension stored in the body.',
                'category' => 'mindfulness',
            ];
        }
        
        if ($mood === 'tired' || $mood === 'stressed') {
            $recommendations[] = [
                'title' => 'Take a 20-Minute Walk',
                'description' => 'Physical activity releases endorphins and helps clear your mind. Even a short walk can significantly reduce stress.',
                'category' => 'exercise',
            ];
        }
        
        if ($stressLevel <= 4) {
            $recommendations[] = [
                'title' => 'Maintain Your Routine',
                'description' => 'Your stress levels are good! Continue your current self-care practices to maintain this balance.',
                'category' => 'mindfulness',
            ];
        }
        
        $recommendations[] = [
            'title' => 'Prioritize Sleep Tonight',
            'description' => 'Aim for 7-8 hours of quality sleep. Avoid screens 1 hour before bed and create a relaxing bedtime routine.',
            'category' => 'sleep',
        ];
        
        $recommendations[] = [
            'title' => 'Practice the Pomodoro Technique',
            'description' => 'Study in 25-minute focused sessions with 5-minute breaks. This prevents burnout and maintains productivity.',
            'category' => 'study_habits',
        ];
        
        return array_slice($recommendations, 0, 3);
    }
    
    /**
     * Analyze mood trends and provide insights
     */
    public function analyzeTrends(array $checkins): array
    {
        if (count($checkins) < 3) {
            return [
                'trend' => 'insufficient_data',
                'message' => 'Keep tracking your wellbeing daily to see trends.',
            ];
        }
        
        $stressLevels = array_map(fn($c) => $c->getStressLevelWell(), $checkins);
        $firstAvg = array_sum(array_slice($stressLevels, -3)) / 3;
        $lastAvg = array_sum(array_slice($stressLevels, 0, 3)) / 3;
        
        if ($lastAvg < $firstAvg - 1) {
            return [
                'trend' => 'improving',
                'message' => 'Great job! Your stress levels are trending downward. Keep up the good work!',
            ];
        } elseif ($lastAvg > $firstAvg + 1) {
            return [
                'trend' => 'increasing',
                'message' => 'Your stress levels have been increasing. Consider using more coping tools or speaking with a counselor.',
            ];
        }
        
        return [
            'trend' => 'stable',
            'message' => 'Your stress levels have been stable. Continue monitoring and practicing self-care.',
        ];
    }

    /**
     * Generate one short quote by type.
     * Supported: motivation, funny, calm, focus
     */
    public function generateMotivationQuote(string $type = 'motivation'): array
    {
        $normalizedType = strtolower(trim($type));
        $aliases = [
            'fanny' => 'funny',
            'fun' => 'funny',
            'motivational' => 'motivation',
            'relax' => 'calm',
            'calme' => 'calm',
            'study' => 'focus',
        ];
        if (isset($aliases[$normalizedType])) {
            $normalizedType = $aliases[$normalizedType];
        }
        if (!in_array($normalizedType, ['motivation', 'funny', 'calm', 'focus'], true)) {
            $normalizedType = 'motivation';
        }

        $fallbackByType = [
            'motivation' => [
                'Small progress every day beats perfect plans.',
                'Breathe. Focus on the next step, not the whole mountain.',
                'You do not need to finish everything today. Keep moving.',
                'Rest is part of performance, not the opposite of it.',
                'Your effort today is building tomorrow\'s confidence.',
            ],
            'funny' => [
                'If stress had homework, we would both ignore it tonight.',
                'Deep breath. You are not a robot, even on deadline mode.',
                'Your brain is buffering. Hydrate and press refresh.',
                'Study plan: tea, tiny steps, dramatic success later.',
                'Even Wi-Fi drops. You can pause and reconnect too.',
            ],
            'calm' => [
                'Slow breath in, slower breath out. You are safe now.',
                'Soft shoulders, relaxed jaw, one quiet moment at a time.',
                'Let today be gentle. Peace grows in small pauses.',
                'Calm is a skill. Practice one breath at a time.',
                'Be kind to yourself. Recovery is productive too.',
            ],
            'focus' => [
                'One task. One timer. One win. Repeat.',
                'Start small, stay steady, finish strong.',
                'Close distractions. Open your next step.',
                'Progress loves consistency more than intensity.',
                'Done is built from focused minutes, not perfect hours.',
            ],
        ];
        $fallback = $fallbackByType[$normalizedType];

        if (empty($this->apiKey)) {
            return [
                'quote' => $fallback[array_rand($fallback)],
                'source' => 'fallback',
                'type' => $normalizedType,
            ];
        }

        try {
            $styleByType = [
                'motivation' => 'motivational',
                'funny' => 'lightly funny',
                'calm' => 'calming',
                'focus' => 'focus-oriented',
            ];
            $style = $styleByType[$normalizedType] ?? 'motivational';

            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Return one short '.$style.' quote for stressed students. 8 to 18 words.'],
                        ['role' => 'user', 'content' => 'Type: '.$normalizedType.'. Give one quote only, no hashtags, no emojis.'],
                    ],
                    'temperature' => 0.9,
                    'max_tokens' => 60,
                ],
            ]);

            $data = $response->toArray(false);
            $quote = trim((string)($data['choices'][0]['message']['content'] ?? ''));
            if ($quote === '') {
                throw new \RuntimeException('Empty quote');
            }

            return [
                'quote' => $quote,
                'source' => 'ai',
                'type' => $normalizedType,
            ];
        } catch (\Throwable $e) {
            return [
                'quote' => $fallback[array_rand($fallback)],
                'source' => 'fallback',
                'type' => $normalizedType,
            ];
        }
    }

    /**
     * Detect likely speech language for journal dictation.
     * Returns one of: en-US, fr-FR, ar-SA, ar-TN
     */
    public function detectSpeechLanguage(string $text): array
    {
        $sample = trim(function_exists('mb_substr') ? mb_substr($text, 0, 400) : substr($text, 0, 400));
        if ($sample === '') {
            return ['languageCode' => 'en-US', 'label' => 'English', 'source' => 'fallback'];
        }

        // Fast fallback first: Arabic script check
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $sample)) {
            $tnHints = ['barsha', '3lech', 'chnowa', 'brabi', 'yesser', 'tawa', 'behi', 'mouch', 'famma', 'ya3ni'];
            $lower = function_exists('mb_strtolower') ? mb_strtolower($sample) : strtolower($sample);
            foreach ($tnHints as $hint) {
                if (str_contains($lower, $hint)) {
                    return ['languageCode' => 'ar-TN', 'label' => 'Tunisian Arabic', 'source' => 'fallback'];
                }
            }
            return ['languageCode' => 'ar-SA', 'label' => 'Arabic', 'source' => 'fallback'];
        }

        // Latin-script heuristic fallback
        $lower = function_exists('mb_strtolower') ? mb_strtolower($sample) : strtolower($sample);
        $frTokens = ['bonjour', 'merci', 'je ', 'suis', 'avec', 'pour', 'cest', 'ça', 'pas', 'oui'];
        $enTokens = ['hello', 'thanks', 'i ', 'am', 'with', 'for', 'this', 'not', 'yes'];

        $frScore = 0;
        $enScore = 0;
        foreach ($frTokens as $token) {
            if (str_contains($lower, $token)) {
                $frScore++;
            }
        }
        foreach ($enTokens as $token) {
            if (str_contains($lower, $token)) {
                $enScore++;
            }
        }

        if ($frScore > $enScore) {
            return ['languageCode' => 'fr-FR', 'label' => 'Francais', 'source' => 'fallback'];
        }
        if ($enScore > 0) {
            return ['languageCode' => 'en-US', 'label' => 'English', 'source' => 'fallback'];
        }

        return ['languageCode' => 'en-US', 'label' => 'English', 'source' => 'fallback'];
    }
}
