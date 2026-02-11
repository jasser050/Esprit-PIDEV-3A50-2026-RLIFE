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
}