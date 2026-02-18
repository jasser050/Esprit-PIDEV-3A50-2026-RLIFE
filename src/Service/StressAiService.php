<?php

namespace App\Service;

class StressAiService
{
    public function generate(int $score): array
    {
        // score sur 40 (ex: 0-40)
        if ($score >= 30) {
            return [
                'level' => 'high',
                'title' => 'High stress detected',
                'message' => 'Try breathing + short walk + consider talking to a professional if it persists.',
                'actions' => ['breathing', 'meditation', 'sleep', 'boundaries'],
            ];
        }

        if ($score >= 16) {
            return [
                'level' => 'medium',
                'title' => 'Moderate stress',
                'message' => 'You may benefit from a routine: pomodoro + hydration + 5min breaks.',
                'actions' => ['pomodoro', 'hydration', 'breaks'],
            ];
        }

        return [
            'level' => 'low',
            'title' => 'Low stress',
            'message' => 'Keep your habits: sleep, planning, and small gratitude journal.',
            'actions' => ['gratitude', 'sleep'],
        ];
    }
}
