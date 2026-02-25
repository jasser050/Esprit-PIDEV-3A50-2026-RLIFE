<?php

namespace App\Controller;

use App\Entity\StudentGamification;
use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/galaxy')]
class GalaxyController extends AbstractController
{
    public function __construct(
        private HttpClientInterface    $httpClient,
        private EntityManagerInterface $em,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // AI STAR NAME
    // Generates a unique cosmic name + star type for this user's star.
    // Result is cached in UserSettings so it's only generated once.
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/star-name', name: 'galaxy_star_name', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function starName(): JsonResponse
    {
        /** @var User $user */
        $user     = $this->getUser();
        $settings = $this->em->getRepository(UserSettings::class)->findOneBy(['user' => $user]);

        // Return cached star if already generated
        if ($settings && $settings->getStarName()) {
            return $this->json([
                'starName' => $settings->getStarName(),
                'starType' => $settings->getStarType(),
                'cached'   => true,
            ]);
        }

        // Build AI prompt from user profile
        $studyLevel  = $settings?->getStudyLevel()  ?? 'intermediate';
        $weeklyGoal  = $settings?->getWeeklyGoal()  ?? 10;
        $interests   = implode(', ', $settings?->getInterests() ?? ['learning']);
        $memberDays  = $user->getCreatedAt()
            ? (new \DateTimeImmutable())->diff($user->getCreatedAt())->days
            : 0;
        $username    = $user->getUsername() ?? $user->getFirstName();

        $prompt = <<<PROMPT
You are a cosmic naming oracle. A student named "{$username}" has the following profile:
- Study level: {$studyLevel}
- Weekly goal: {$weeklyGoal} hours
- Interests: {$interests}
- Member for: {$memberDays} days

Generate a unique cosmic star name and classify the star type. 
Respond with ONLY valid JSON in exactly this format:
{"starName": "...", "starType": "...", "tagline": "..."}

Rules:
- starName: a unique 2-4 word cosmic name (e.g. "Vega-9, The Relentless Seeker", "Cygnus-7, The Midnight Scholar")
- starType: EXACTLY one of: blue_dwarf | yellow_giant | red_supergiant | white_nova | neutron_wanderer
  (choose based on personality: beginners=blue_dwarf, steady learners=yellow_giant, advanced=red_supergiant, creative=white_nova, explorers=neutron_wanderer)
- tagline: a single poetic sentence (max 12 words) about this star
PROMPT;

        $aiResponse = $this->callOpenRouter($prompt);

        if (!$aiResponse) {
            // Fallback
            return $this->json([
                'starName' => $username . ', The Rising Star',
                'starType' => 'yellow_giant',
                'tagline'  => 'A steady light burning bright in the cosmos.',
                'cached'   => false,
            ]);
        }

        // Parse JSON from AI
        $data = json_decode($aiResponse, true);
        if (!$data || !isset($data['starName'])) {
            // Try to extract JSON if AI added extra text
            preg_match('/\{.*\}/s', $aiResponse, $m);
            $data = $m ? json_decode($m[0], true) : null;
        }

        $starName = $data['starName'] ?? $username . ', The Rising Star';
        $starType = $data['starType'] ?? 'yellow_giant';
        $tagline  = $data['tagline']  ?? 'A steady light burning bright in the cosmos.';

        // Validate starType
        $validTypes = ['blue_dwarf', 'yellow_giant', 'red_supergiant', 'white_nova', 'neutron_wanderer'];
        if (!in_array($starType, $validTypes)) {
            $starType = 'yellow_giant';
        }

        // Cache in UserSettings
        if ($settings) {
            $settings->setStarName($starName);
            $settings->setStarType($starType);
            $this->em->flush();
        }

        return $this->json([
            'starName' => $starName,
            'starType' => $starType,
            'tagline'  => $tagline,
            'cached'   => false,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AI STAR FORECAST
    // Predicts how the user's star will evolve in 30 days based on
    // their gamification data (streak, points, weekly goal).
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/star-forecast', name: 'galaxy_star_forecast', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function starForecast(): JsonResponse
    {
        /** @var User $user */
        $user     = $this->getUser();
        $settings = $this->em->getRepository(UserSettings::class)->findOneBy(['user' => $user]);
        $gamif    = $this->em->getRepository(StudentGamification::class)->findOneBy(['user' => $user]);

        $starName = $settings?->getStarName() ?? ($user->getFirstName() . "'s Star");
        $starType = $settings?->getStarType() ?? 'yellow_giant';
        $streak   = $gamif?->getStreak()               ?? 0;
        $points   = $gamif?->getPoints()                ?? 0;
        $decks    = $gamif?->getTotalDecksCompleted()   ?? 0;
        $correct  = $gamif?->getTotalCorrectAnswers()   ?? 0;
        $weeklyGoal = $settings?->getWeeklyGoal()       ?? 10;

        $prompt = <<<PROMPT
You are an astral forecaster for a student learning platform. 
Star name: "{$starName}" (currently a {$starType}).
Current stats:
- Study streak: {$streak} days
- Total XP: {$points}
- Decks completed: {$decks}
- Correct answers: {$correct}
- Weekly study goal: {$weeklyGoal} hours

Predict this star's evolution in 30 days if the student maintains current pace.
Respond with ONLY valid JSON in exactly this format:
{"forecast": "...", "futureType": "...", "daysToEvolve": 0, "momentum": "rising|stable|fading"}

Rules:
- forecast: ONE dramatic sentence (max 15 words) about the star's future
- futureType: EXACTLY one of: blue_dwarf | yellow_giant | red_supergiant | white_nova | neutron_wanderer
- daysToEvolve: integer 5-30 (days until next evolution)
- momentum: "rising" if streak>3 or points>100, "stable" if moderate, "fading" if streak=0
PROMPT;

        $aiResponse = $this->callOpenRouter($prompt);

        $data = null;
        if ($aiResponse) {
            $data = json_decode($aiResponse, true);
            if (!$data) {
                preg_match('/\{.*\}/s', $aiResponse, $m);
                $data = $m ? json_decode($m[0], true) : null;
            }
        }

        $validTypes = ['blue_dwarf', 'yellow_giant', 'red_supergiant', 'white_nova', 'neutron_wanderer'];

        return $this->json([
            'currentType'  => $starType,
            'currentName'  => $starName,
            'forecast'     => $data['forecast']     ?? 'Your star burns with quiet determination.',
            'futureType'   => in_array($data['futureType'] ?? '', $validTypes) ? $data['futureType'] : 'yellow_giant',
            'daysToEvolve' => $data['daysToEvolve'] ?? 14,
            'momentum'     => in_array($data['momentum'] ?? '', ['rising', 'stable', 'fading']) ? $data['momentum'] : 'stable',
            'stats'        => [
                'streak'  => $streak,
                'points'  => $points,
                'decks'   => $decks,
                'correct' => $correct,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // USERS DATA (Admin only)
    // Returns all users as JSON for the admin galaxy renderer.
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/users-data', name: 'galaxy_users_data', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function usersData(): JsonResponse
    {
        $users = $this->em->getRepository(User::class)->findAll();
        $now   = new \DateTimeImmutable();

        $data = array_map(function (User $u) use ($now): array {
            $daysOld = $u->getCreatedAt()
                ? $now->diff($u->getCreatedAt())->days
                : 0;

            $role = in_array('ROLE_ADMIN', $u->getRoles()) ? 'admin' : 'user';

            return [
                'id'      => $u->getId(),
                'name'    => $u->getFirstName() . ' ' . $u->getLastName(),
                'role'    => $role,
                'banned'  => $u->isBanned(),
                'daysOld' => $daysOld,
            ];
        }, $users);

        return $this->json($data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal: call OpenRouter API
    // ─────────────────────────────────────────────────────────────────────────

    private function callOpenRouter(string $prompt): ?string
    {
        $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';
        if (!$apiKey) return null;

        try {
            $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => 'http://localhost:8000',
                    'X-Title'       => 'RLIFE Galaxy',
                ],
                'json' => [
                    'model'       => 'mistralai/mistral-7b-instruct:free',
                    'messages'    => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens'  => 120,
                    'temperature' => 0.8,
                ],
                'timeout' => 15,
            ]);

            $body = $response->toArray(false);
            return $body['choices'][0]['message']['content'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
