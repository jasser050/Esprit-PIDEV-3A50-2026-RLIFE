<?php

namespace App\Service;

use App\Entity\User;

class CityService
{
    public function __construct(
        private StreakService $streakService,
    ) {}

    // ══════════════════════════════════════════
    // POINT D'ENTRÉE PRINCIPAL
    // ══════════════════════════════════════════
    public function getCityData(User $user): array
    {
        $totalCoins     = $this->calculateCoins($user);
        $totalBuildings = $this->countBuildings($user);
        $streak         = $this->streakService->getHighScoreStreak($user);
        $countries      = $this->getUnlockedCountries($streak['current']);
        $buildings      = $this->generateBuildings($totalBuildings);
        $level          = max(1, (int)($totalCoins / 500) + 1);
        $population     = $totalCoins * 2;
        $nextLevelCoins = $level * 500;

        return [
            'coins'           => $totalCoins,
            'level'           => $level,
            'next_level_coins'=> $nextLevelCoins,
            'xp_percent'      => min(100, (int)(($totalCoins % 500) / 500 * 100)),
            'buildings'       => $buildings,
            'countries'       => $countries,
            'population'      => $population,
            'streak'          => $streak,
            'total_evals'     => $user->getEvaluations()->count(),
            'stats'           => $this->getCityStats($user),
        ];
    }

    // ══════════════════════════════════════════
    // CALCUL COINS (depuis évaluations existantes)
    // ══════════════════════════════════════════
    public function calculateCoins(User $user): int
    {
        $totalCoins = 0;
        $streak     = 0;

        $evaluations = $user->getEvaluations()->toArray();
        usort($evaluations, fn($a, $b) => $a->getDateEvaluation() <=> $b->getDateEvaluation());

        foreach ($evaluations as $eval) {
            $pct = $eval->getPercentage();

            // Coins de base selon score
            if ($pct >= 90)      $coins = 250;
            elseif ($pct >= 80)  $coins = 150;
            elseif ($pct >= 75)  $coins = 100;
            elseif ($pct >= 60)  $coins = 50;
            else                 $coins = 20;

            // Streak bonus
            if ($pct >= 75) {
                $streak++;
                if ($streak >= 3) $coins += 200;
            } else {
                $streak = 0;
            }

            $totalCoins += $coins;
        }

        return $totalCoins;
    }

    // ══════════════════════════════════════════
    // BÂTIMENTS — 1 par éval réussie (≥75%)
    // ══════════════════════════════════════════
    private function countBuildings(User $user): int
    {
        $count = 0;
        foreach ($user->getEvaluations() as $eval) {
            if ($eval->getPercentage() >= 75) $count++;
        }
        return $count;
    }

    public function generateBuildings(int $count): array
    {
        $types = [
            ['id'=>'library',    'icon'=>'📚', 'name'=>'Bibliothèque',  'color'=>'#3b82f6', 'bonus'=>'+5% XP Math'],
            ['id'=>'lab',        'icon'=>'🔬', 'name'=>'Laboratoire',   'color'=>'#10b981', 'bonus'=>'+10% XP Science'],
            ['id'=>'cafe',       'icon'=>'☕', 'name'=>'Café',          'color'=>'#92400e', 'bonus'=>'+Motivation'],
            ['id'=>'museum',     'icon'=>'🏛', 'name'=>'Musée',         'color'=>'#c9a84c', 'bonus'=>'Quiz culture'],
            ['id'=>'university', 'icon'=>'🎓', 'name'=>'Université',    'color'=>'#8b5cf6', 'bonus'=>'+25% XP'],
            ['id'=>'stadium',    'icon'=>'🏟', 'name'=>'Stade',         'color'=>'#ef4444', 'bonus'=>'+Streak bonus'],
            ['id'=>'hospital',   'icon'=>'🏥', 'name'=>'Hôpital',       'color'=>'#fca5a5', 'bonus'=>'Streak protect'],
            ['id'=>'tower',      'icon'=>'🏆', 'name'=>'Tour d\'or',    'color'=>'#c9a84c', 'bonus'=>'×2 coins'],
        ];

        $slots = [
            ['x'=>-8,'z'=>-8], ['x'=>8,'z'=>-8],
            ['x'=>-8,'z'=>8],  ['x'=>8,'z'=>8],
            ['x'=>0,'z'=>-12], ['x'=>-12,'z'=>0],
            ['x'=>12,'z'=>0],  ['x'=>0,'z'=>12],
            ['x'=>-14,'z'=>6], ['x'=>14,'z'=>6],
            ['x'=>-6,'z'=>14], ['x'=>6,'z'=>-14],
        ];

        $buildings = [];
        $max = min($count, count($slots));
        for ($i = 0; $i < $max; $i++) {
            $type = $types[$i % count($types)];
            $buildings[] = array_merge($type, ['slot' => $slots[$i], 'level' => 1]);
        }

        return $buildings;
    }

    // ══════════════════════════════════════════
    // PAYS DÉBLOQUÉS selon streak
    // ══════════════════════════════════════════
    public function getUnlockedCountries(int $streak): array
    {
        $all = $this->getAllCountries();
        return array_filter($all, fn($c) => $streak >= $c['streak']);
    }

    public function getAllCountries(): array
    {
        return [
            ['id'=>'france',    'name'=>'France',     'city'=>'Paris',        'flag'=>'🇫🇷', 'monument'=>'🗼', 'monumentName'=>'Tour Eiffel',       'streak'=>1,  'color'=>'#4a9e5c'],
            ['id'=>'egypt',     'name'=>'Egypte',     'city'=>'Cairo',        'flag'=>'🇪🇬', 'monument'=>'🏛', 'monumentName'=>'Pyramides',          'streak'=>2,  'color'=>'#c9a84c'],
            ['id'=>'japan',     'name'=>'Japon',      'city'=>'Tokyo',        'flag'=>'🇯🇵', 'monument'=>'⛩', 'monumentName'=>'Mont Fuji',          'streak'=>3,  'color'=>'#e53e3e'],
            ['id'=>'brazil',    'name'=>'Brésil',     'city'=>'Rio',          'flag'=>'🇧🇷', 'monument'=>'🗿', 'monumentName'=>'Christ Rédempteur', 'streak'=>4,  'color'=>'#38a169'],
            ['id'=>'uae',       'name'=>'Émirats',    'city'=>'Dubai',        'flag'=>'🇦🇪', 'monument'=>'🏙', 'monumentName'=>'Burj Khalifa',       'streak'=>5,  'color'=>'#3182ce'],
            ['id'=>'usa',       'name'=>'USA',        'city'=>'New York',     'flag'=>'🇺🇸', 'monument'=>'🗽', 'monumentName'=>'Statue Liberté',    'streak'=>6,  'color'=>'#805ad5'],
            ['id'=>'india',     'name'=>'Inde',       'city'=>'Mumbai',       'flag'=>'🇮🇳', 'monument'=>'🕌', 'monumentName'=>'Taj Mahal',          'streak'=>7,  'color'=>'#dd6b20'],
            ['id'=>'australia', 'name'=>'Australie',  'city'=>'Sydney',       'flag'=>'🇦🇺', 'monument'=>'🎭', 'monumentName'=>'Opéra Sydney',       'streak'=>8,  'color'=>'#2b6cb0'],
            ['id'=>'kenya',     'name'=>'Kenya',      'city'=>'Nairobi',      'flag'=>'🇰🇪', 'monument'=>'🦁', 'monumentName'=>'Parc National',      'streak'=>9,  'color'=>'#276749'],
            ['id'=>'china',     'name'=>'Chine',      'city'=>'Beijing',      'flag'=>'🇨🇳', 'monument'=>'🐉', 'monumentName'=>'Grande Muraille',    'streak'=>10, 'color'=>'#c53030'],
            ['id'=>'argentina', 'name'=>'Argentine',  'city'=>'Buenos Aires', 'flag'=>'🇦🇷', 'monument'=>'💃', 'monumentName'=>'Casa Rosada',        'streak'=>12, 'color'=>'#2c7a7b'],
            ['id'=>'russia',    'name'=>'Russie',     'city'=>'Moscow',       'flag'=>'🇷🇺', 'monument'=>'🏰', 'monumentName'=>'Kremlin',            'streak'=>15, 'color'=>'#553c9a'],
        ];
    }

    // ══════════════════════════════════════════
    // STATS VILLE
    // ══════════════════════════════════════════
    private function getCityStats(User $user): array
    {
        $evals     = $user->getEvaluations();
        $success   = 0;
        $elite     = 0;
        $total     = $evals->count();

        foreach ($evals as $eval) {
            $pct = $eval->getPercentage();
            if ($pct >= 90) { $elite++; $success++; }
            elseif ($pct >= 75) $success++;
        }

        return [
            'total_evals'   => $total,
            'success'       => $success,
            'elite'         => $elite,
            'success_rate'  => $total > 0 ? round($success / $total * 100) : 0,
        ];
    }
}
