<?php

namespace App\Controller;

use App\Data\SampleData;
use App\Entity\CopingSession;
use App\Entity\User;
use App\Entity\WellbeingJournalEntry;
use App\Repository\CopingSessionRepository;
use App\Repository\WellbeingJournalEntryRepository;
use App\Service\OpenRouterService;
use App\Service\WellbeingAiService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/wellbeing/tools')]
class CopingToolsController extends AbstractController
{
    #[Route('', name: 'app_wellbeing_tools', methods: ['GET'])]
    public function index(
        CopingSessionRepository $sessionRepo,
        ManagerRegistry $doctrine,
        ?WellbeingJournalEntryRepository $journalRepo = null
    ): Response
    {
        if ($journalRepo === null) {
            $repo = $doctrine->getRepository(WellbeingJournalEntry::class);
            if ($repo instanceof WellbeingJournalEntryRepository) {
                $journalRepo = $repo;
            }
        }

        $user = $this->getUser();
        $recentSessions = [];
        $journalEntries = [];

        if ($user instanceof User) {
            $recentSessions = $sessionRepo->findBy(
                ['user' => $user],
                ['createdAt' => 'DESC'],
                5
            );
            if ($journalRepo !== null) {
                $journalEntries = $journalRepo->findByUser($user, 50);
            }
        }

        // Calculate stats
        $totalSessions = count($recentSessions);
        $totalMinutes = 0;
        $completedSessions = 0;

        foreach ($recentSessions as $session) {
            if ($session->getActualSeconds()) {
                $totalMinutes += $session->getActualSeconds() / 60;
            }
            if ($session->getStatus() === 'finished') {
                $completedSessions++;
            }
        }

        return $this->render('pages/wellbeing/tools.html.twig', [
            'tools' => SampleData::getCopingTools(),
            'recent_sessions' => $recentSessions,
            'total_sessions' => $totalSessions,
            'total_minutes' => round($totalMinutes),
            'completed_sessions' => $completedSessions,
            'journal_entries' => $journalEntries,
        ]);
    }

    #[Route('/start', name: 'app_wellbeing_tools_start', methods: ['POST'])]
    public function start(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['ok' => false, 'message' => 'Invalid JSON'], 400);
        }

        $toolKey = (string)($data['toolKey'] ?? '');
        if ($toolKey === '') {
            return $this->json(['ok' => false, 'message' => 'toolKey required'], 400);
        }

        $tool = null;
        foreach (SampleData::getCopingTools() as $t) {
            if (($t['key'] ?? '') === $toolKey) { $tool = $t; break; }
        }
        if (!$tool) {
            return $this->json(['ok' => false, 'message' => 'Tool not found'], 404);
        }

        $session = new CopingSession();

        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $session->setUser($user);
        }

        $session->setToolKey($toolKey);
        $session->setToolName((string)($tool['name'] ?? $toolKey));
        $session->setDurationSeconds((int)($tool['durationSeconds'] ?? 0));
        $session->setStartedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $session->setStatus('started');
        $session->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));

        $em->persist($session);
        $em->flush();

        return $this->json([
            'ok' => true,
            'sessionId' => $session->getId(),
            'toolKey' => $session->getToolKey(),
            'toolName' => $session->getToolName(),
            'durationSeconds' => $session->getDurationSeconds(),
        ]);
    }

    #[Route('/finish', name: 'app_wellbeing_tools_finish', methods: ['POST'])]
    public function finish(Request $request, CopingSessionRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['ok' => false, 'message' => 'Invalid JSON'], 400);
        }

        $sessionId = (int)($data['sessionId'] ?? 0);
        if ($sessionId <= 0) {
            return $this->json(['ok' => false, 'message' => 'sessionId required'], 400);
        }

        $session = $repo->find($sessionId);
        if (!$session) {
            return $this->json(['ok' => false, 'message' => 'Session not found'], 404);
        }

        if ($session->getStatus() !== 'started') {
            return $this->json(['ok' => true, 'message' => 'Already closed', 'status' => $session->getStatus()]);
        }

        $status = (string)($data['status'] ?? 'finished');
        if (!in_array($status, ['finished', 'cancelled'], true)) {
            $status = 'finished';
        }

        $actualSeconds = (int)($data['actualSeconds'] ?? 0);
        $finish = new \DateTime();

        if ($actualSeconds <= 0) {
            $startedAt = \DateTime::createFromFormat('Y-m-d H:i:s', $session->getStartedAt());
            if ($startedAt) {
                $actualSeconds = max(1, $finish->getTimestamp() - $startedAt->getTimestamp());
            }
        }

        $session->setFinishedAt($finish->format('Y-m-d H:i:s'));
        $session->setActualSeconds($actualSeconds);
        $session->setStatus($status);
        $session->setUpdatedAt($finish->format('Y-m-d H:i:s'));

        $em->flush();

        return $this->json([
            'ok' => true,
            'status' => $session->getStatus(),
            'actualSeconds' => $session->getActualSeconds(),
        ]);
    }

    #[Route('/journal', name: 'app_wellbeing_tools_journal_list', methods: ['GET'])]
    public function journalList(WellbeingJournalEntryRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $rows = array_map(static function (WellbeingJournalEntry $entry): array {
            return [
                'id' => $entry->getId(),
                'content' => $entry->getContent(),
                'languageCode' => $entry->getLanguageCode(),
                'inputMode' => $entry->getInputMode(),
                'createdAt' => $entry->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $entry->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $repo->findByUser($user, 100));

        return $this->json(['ok' => true, 'entries' => $rows]);
    }

    #[Route('/journal', name: 'app_wellbeing_tools_journal_create', methods: ['POST'])]
    public function journalCreate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['ok' => false, 'message' => 'Invalid JSON'], 400);
        }

        $content = trim((string)($payload['content'] ?? ''));
        if ($content === '') {
            return $this->json(['ok' => false, 'message' => 'Content required'], 400);
        }

        $entry = new WellbeingJournalEntry();
        $entry->setUser($user);
        $entry->setContent($content);
        $entry->setLanguageCode(($payload['languageCode'] ?? null) ?: null);
        $entry->setInputMode((string)($payload['inputMode'] ?? 'text'));
        $entry->setCreatedAt(new \DateTime());

        $em->persist($entry);
        $em->flush();

        return $this->json([
            'ok' => true,
            'entry' => [
                'id' => $entry->getId(),
                'content' => $entry->getContent(),
                'languageCode' => $entry->getLanguageCode(),
                'inputMode' => $entry->getInputMode(),
                'createdAt' => $entry->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $entry->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    #[Route('/journal/{id}', name: 'app_wellbeing_tools_journal_update', methods: ['PUT'])]
    public function journalUpdate(int $id, Request $request, WellbeingJournalEntryRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $entry = $repo->find($id);
        if (!$entry || $entry->getUser()?->getId() !== $user->getId()) {
            return $this->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['ok' => false, 'message' => 'Invalid JSON'], 400);
        }

        $content = trim((string)($payload['content'] ?? ''));
        if ($content === '') {
            return $this->json(['ok' => false, 'message' => 'Content required'], 400);
        }

        $entry->setContent($content);
        $entry->setLanguageCode(($payload['languageCode'] ?? null) ?: $entry->getLanguageCode());
        $entry->setInputMode((string)($payload['inputMode'] ?? $entry->getInputMode()));
        $entry->setUpdatedAt(new \DateTime());
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/journal/{id}', name: 'app_wellbeing_tools_journal_delete', methods: ['DELETE'])]
    public function journalDelete(int $id, WellbeingJournalEntryRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $entry = $repo->find($id);
        if (!$entry || $entry->getUser()?->getId() !== $user->getId()) {
            return $this->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $em->remove($entry);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/journal/detect-language', name: 'app_wellbeing_tools_journal_detect_language', methods: ['POST'])]
    public function journalDetectLanguage(Request $request, WellbeingAiService $wellbeingAiService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['ok' => false, 'message' => 'Invalid JSON'], 400);
        }

        $text = trim((string)($payload['text'] ?? ''));
        if ($text === '') {
            return $this->json(['ok' => false, 'message' => 'Text required'], 400);
        }

        $result = $wellbeingAiService->detectSpeechLanguage($text);

        return $this->json([
            'ok' => true,
            'languageCode' => $result['languageCode'],
            'label' => $result['label'],
            'source' => $result['source'],
        ]);
    }

    #[Route('/ai-chat', name: 'app_wellbeing_tools_ai_chat', methods: ['POST'])]
    public function aiChat(
        Request $request,
        OpenRouterService $openRouterService,
        WellbeingAiService $wellbeingAiService
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['ok' => false, 'message' => 'Invalid JSON'], 400);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return $this->json(['ok' => false, 'message' => 'Message required'], 400);
        }
        if ((function_exists('mb_strlen') ? mb_strlen($message) : strlen($message)) > 1500) {
            $message = function_exists('mb_substr') ? mb_substr($message, 0, 1500) : substr($message, 0, 1500);
        }

        $history = is_array($payload['history'] ?? null) ? $payload['history'] : [];
        $history = array_slice($history, -12);

        $langInfo = $wellbeingAiService->detectSpeechLanguage($message);
        $languageCode = (string) ($langInfo['languageCode'] ?? 'en-US');
        $languageCode = $this->guessLanguageFromMessage($message, $languageCode);
        $languageInstruction = $this->languageInstructionFromCode($languageCode);

        if ($this->isGreetingMessage($message)) {
            return $this->json([
                'ok' => true,
                'reply' => $this->buildGreetingReply($languageCode),
                'source' => 'greeting',
            ]);
        }

        $messages = [[
            'role' => 'system',
            'content' => <<<PROMPT
You are RLIFE Wellbeing AI Coach for students.
Rules:
- Talk like a friendly, supportive peer (Snapchat-style). Keep it short, natural, and warm.
- Reply ONLY in {$languageInstruction}.
- Respond to the student's message content directly. Avoid generic scripts.
- If advice is needed, keep it practical with 1-2 concrete next steps.
- Ask one simple follow-up question to keep the conversation going.
- If the student asks for motivation, provide encouraging and realistic guidance.
- If user mentions self-harm, suicide, or danger, respond with empathy and strongly suggest immediate local emergency/crisis help.
- Do not pretend to be a doctor, but provide safe wellbeing guidance.
PROMPT
        ]];

        foreach ($history as $turn) {
            $role = (string) ($turn['role'] ?? '');
            $content = trim((string) ($turn['content'] ?? ''));
            if (!in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }
            $messages[] = [
                'role' => $role,
                'content' => function_exists('mb_substr') ? mb_substr($content, 0, 1500) : substr($content, 0, 1500),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $reply = $openRouterService->chat($messages, [
            'model' => 'anthropic/claude-3-haiku',
            'temperature' => 0.8,
            'max_tokens' => 700,
        ]);

        if ($reply === null || trim($reply) === '') {
            return $this->json([
                'ok' => true,
                'reply' => $this->buildLocalizedFallbackReply($message, $languageCode),
                'source' => 'fallback',
            ]);
        }

        $cleanReply = trim($reply);
        $replyLang = $wellbeingAiService->detectSpeechLanguage($cleanReply);
        $replyCode = (string) ($replyLang['languageCode'] ?? 'en-US');

        if ($replyCode !== $languageCode) {
            $rewrite = $openRouterService->chat([
                [
                    'role' => 'system',
                    'content' => 'Rewrite the following assistant answer ONLY in ' . $languageInstruction . '. Keep same meaning and tone.',
                ],
                [
                    'role' => 'user',
                    'content' => $cleanReply,
                ],
            ], [
                'model' => 'anthropic/claude-3-haiku',
                'temperature' => 0.4,
                'max_tokens' => 700,
            ]);

            if (is_string($rewrite) && trim($rewrite) !== '') {
                $cleanReply = trim($rewrite);
            }
        }

        return $this->json([
            'ok' => true,
            'reply' => $cleanReply,
            'source' => 'ai',
        ]);
    }

    #[Route('/yoga-plan', name: 'app_wellbeing_tools_yoga_plan', methods: ['POST'])]
    public function yogaPlan(Request $request, OpenRouterService $openRouterService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $gender = strtolower((string) ($user->getGender() ?? 'other'));
        $coachGender = match ($gender) {
            'female' => 'female',
            'male' => 'male',
            default => 'female',
        };

        $allowedDemos = ['neck', 'shoulders', 'twist', 'catcow', 'child', 'side', 'wrist', 'ankle', 'breath'];
        $allowedGifs = ['gif1', 'gif2', 'gif3', 'gif4', 'gif5'];

        $prompt = <<<PROMPT
Generate a fresh yoga micro-session for a student.
Coach demonstration gender must be {$coachGender}.
Return ONLY valid JSON with this exact structure:
{
  "plan": [
    {"title":"...", "description":"...", "demo":"neck|shoulders|twist|catcow|child|side|wrist|ankle|breath", "gifKey":"gif1|gif2|gif3|gif4|gif5", "seconds":20}
  ]
}
Rules:
- 6 exercises exactly.
- Keep each title short.
- Description must be one practical sentence.
- Exercises must be low-risk and beginner-friendly.
- Vary from common sequences (do not always repeat the same order).
- Use only the allowed demo and gifKey values above.
PROMPT;

        $reply = $openRouterService->chat([
            ['role' => 'system', 'content' => 'You are a safe yoga routine generator for students. Output JSON only.'],
            ['role' => 'user', 'content' => $prompt],
        ], [
            'model' => 'anthropic/claude-3-haiku',
            'temperature' => 0.95,
            'max_tokens' => 900,
        ]);

        $plan = $this->extractYogaPlanFromAiReply((string) $reply, $allowedDemos, $allowedGifs);
        $source = 'ai';

        if ($plan === []) {
            $plan = $this->buildYogaFallbackPlan($allowedDemos, $allowedGifs);
            $source = 'fallback';
        }

        return $this->json([
            'ok' => true,
            'coachGender' => $coachGender,
            'plan' => $plan,
            'source' => $source,
        ]);
    }

    private function languageInstructionFromCode(string $languageCode): string
    {
        return match ($languageCode) {
            'fr-FR' => 'French',
            'ar-TN' => 'Tunisian Arabic',
            'ar-SA' => 'Arabic',
            default => 'English',
        };
    }

    private function buildLocalizedFallbackReply(string $message, string $languageCode): string
    {
        $text = function_exists('mb_strtolower') ? mb_strtolower($message) : strtolower($message);
        $isRisk = $this->isHighRiskMessage($text);
        $wantsMusic = str_contains($text, 'music') || str_contains($text, 'musique') || str_contains($text, 'music');
        $sleep = str_contains($text, 'sleep') || str_contains($text, 'insom') || str_contains($text, 'sommeil') || str_contains($text, 'nraj');
        $stress = str_contains($text, 'stress') || str_contains($text, 'anx') || str_contains($text, 'angois') || str_contains($text, 'قلق');
        $pain = str_contains($text, 'mal') || str_contains($text, 'pain') || str_contains($text, 'douleur') || str_contains($text, 'heart') || str_contains($text, 'coeur');
        $isPhysical = str_contains($text, 'malade')
            || str_contains($text, 'sick')
            || str_contains($text, 'pain')
            || str_contains($text, 'fever')
            || str_contains($text, 'douleur')
            || str_contains($text, 'fievre');

        if ($languageCode === 'fr-FR') {
            if ($isRisk) {
                return 'Je suis vraiment desole que tu traverses ca. Ta securite passe avant tout. Si tu es en danger maintenant, appelle immediatement les urgences de ton pays. Si tu peux, contacte aussi une personne de confiance pres de toi. Je peux rester avec toi et t aider pas a pas.';
            }
            if ($pain || $isPhysical) {
                return 'Je suis desole que tu te sentes mal. Tu peux me dire ou tu as mal et depuis quand ? Si la douleur est forte ou dure, pense a consulter rapidement.';
            }
            if ($sleep) {
                return 'Ah mince, le sommeil c est dur. Tu veux essayer une routine courte (respiration, ecran off 30 min) ou tu veux juste en parler ?';
            }
            if ($wantsMusic) {
                return 'Je peux te proposer des sons doux pour te poser. Tu veux plutot nature, pluie, ou piano ?';
            }
            if ($stress) {
                return 'Je comprends. Tu veux un petit exercice rapide (1-2 minutes) ou on parle de ce qui te stresse ?';
            }
            $samples = [
                'Salut ! Dis-moi ce que tu ressens en ce moment.',
                'Je suis la. Tu veux en parler un peu ?',
                'Ok, je t ecoute. C est quoi le plus dur la tout de suite ?',
            ];
            return $samples[array_rand($samples)];
        }

        if ($languageCode === 'ar-SA' || $languageCode === 'ar-TN') {
            if ($isRisk) {
                return 'Ana ma3ak. Salamtak awwalan. Itha kont fi khatar al-an, ittasel bitawari2 fi baladek fawran, w khalli shakhss thiqa ykoun ma3ak. Najem n3awnek khatwa b khatwa.';
            }
            if ($pain || $isPhysical) {
                return 'Smahni 3ala il waj3. Tnجم t9olli win il waja3 w min wa9t?';
            }
            if ($sleep) {
                return 'Ma tnجمch trقد? T7eb njربou تمرين تنفس sghir wala t7eb t7كي 3lih؟';
            }
            if ($wantsMusic) {
                return 'Nnجم n3tik musique هادية. T7eb nature wala piano?';
            }
            if ($stress) {
                return 'Fhemt. T7eb exercice sghir wala t7ki 3la sbab el stress؟';
            }
            return 'Ana ma3ak. Ahki li chnouwa akther haja mdhayqtek taw?';
        }

        if ($isRisk) {
            return 'I am really sorry you are going through this. Your safety comes first. If you are in immediate danger, call local emergency services now and contact someone you trust nearby. I can stay with you and help step by step.';
        }

        if ($pain || $isPhysical) {
            return 'I am sorry you feel pain. Where does it hurt and since when? If it is strong or getting worse, please seek medical care.';
        }
        if ($sleep) {
            return 'Not sleeping is rough. Want a quick reset routine or to talk it out?';
        }
        if ($wantsMusic) {
            return 'I can suggest calming sounds. Do you want nature, rain, or piano?';
        }
        if ($stress) {
            return 'I get it. Want a 1-minute breathing exercise or to talk about what is stressing you?';
        }

        $samples = [
            'Hey, I am here. What is on your mind?',
            'I am listening. What is the hardest part right now?',
            'Tell me a bit more and we will figure it out together.',
        ];

        return $samples[array_rand($samples)];
    }

    private function guessLanguageFromMessage(string $message, string $fallback): string
    {
        $text = trim($message);
        if ($text === '') {
            return $fallback;
        }

        if (preg_match('/[\\x{0600}-\\x{06FF}]/u', $text)) {
            return 'ar-SA';
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
        $frenchHints = ['bonjour', 'salut', 'merci', 'svp', 's il vous plait', 'je ', 'tu ', 'ça', 'ca ', 'aide', 'stresse', 'stress', 'sommeil', 'mal au'];
        foreach ($frenchHints as $hint) {
            if (str_contains($lower, $hint)) {
                return 'fr-FR';
            }
        }

        $arabicLatnHints = ['salam', 'salem', 'marhba', 'marhaba', 'ahla', 'ya hala', 'chnou', 'chno', 't7eb', 'nheb'];
        foreach ($arabicLatnHints as $hint) {
            if (str_contains($lower, $hint)) {
                return 'ar-TN';
            }
        }

        return $fallback;
    }

    private function isGreetingMessage(string $message): bool
    {
        $text = trim(function_exists('mb_strtolower') ? mb_strtolower($message) : strtolower($message));
        if ($text === '') {
            return false;
        }

        $greetings = [
            'hi', 'hello', 'hey', 'yo', 'sup', 'good morning', 'good afternoon', 'good evening',
            'salut', 'bonjour', 'bonsoir', 'coucou', 'cc',
            'salam', 'salem', 'marhba', 'marhaba', 'ahlan', 'ahlan wa sahlan', 'ahla', 'ya hala',
            'مرحبا', 'السلام', 'السلام عليكم', 'اهلا', 'أهلا', 'أهلا وسهلا', 'يا هلا',
        ];

        foreach ($greetings as $greet) {
            if ($text === $greet || str_starts_with($text, $greet . ' ') || str_starts_with($text, $greet . '!')) {
                return true;
            }
        }

        return false;
    }

    private function buildGreetingReply(string $languageCode): string
    {
        if ($languageCode === 'fr-FR') {
            $samples = [
                'Salut ! Je suis la. Ca va ?',
                'Hello ! Raconte-moi ce qui te passe par la tete.',
                'Coucou ! Besoin de parler de quelque chose ?',
            ];
            return $samples[array_rand($samples)];
        }

        if ($languageCode === 'ar-SA' || $languageCode === 'ar-TN') {
            $samples = [
                'Salut! Ana hne, kifah? Shnouwa t7eb tahki 3lih?',
                'Marhba! Ana ma3ak. Chnouwa fi balek tawa?',
                'Ahlan! Kifk? Nnajm n3awnek kifeh?',
            ];
            return $samples[array_rand($samples)];
        }

        $samples = [
            'Hey! I am here. How are you?',
            'Hi! Want to tell me what is on your mind?',
            'Hey there! How is your day going?',
        ];
        return $samples[array_rand($samples)];
    }

    private function isHighRiskMessage(string $text): bool
    {
        $riskKeywords = [
            'suicide', 'kill myself', 'end my life', 'self harm', 'hurt myself',
            'je veux mourir', 'je veu mourir', 'je veux me tuer', 'me tuer', 'mourir',
            'nheb nmout', 'bech nmout', 'nmout', 'mout',
        ];

        foreach ($riskKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{title:string,description:string,demo:string,gifKey:string,seconds:int}>
     */
    private function extractYogaPlanFromAiReply(string $reply, array $allowedDemos, array $allowedGifs): array
    {
        $reply = trim($reply);
        if ($reply === '') {
            return [];
        }

        $data = json_decode($reply, true);
        if (!is_array($data) && preg_match('/\{[\s\S]*\}/', $reply, $m)) {
            $data = json_decode($m[0], true);
        }

        if (!is_array($data) || !isset($data['plan']) || !is_array($data['plan'])) {
            return [];
        }

        $result = [];
        $seen = [];
        foreach ($data['plan'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $demo = strtolower(trim((string) ($item['demo'] ?? '')));
            $gifKey = strtolower(trim((string) ($item['gifKey'] ?? 'gif1')));
            $seconds = 20;

            if ($title === '' || $description === '' || !in_array($demo, $allowedDemos, true)) {
                continue;
            }
            if (!in_array($gifKey, $allowedGifs, true)) {
                $gifKey = 'gif1';
            }
            $fingerprint = strtolower($title . '|' . $demo);
            if (isset($seen[$fingerprint])) {
                continue;
            }
            $seen[$fingerprint] = true;

            $result[] = [
                'title' => $title,
                'description' => $description,
                'demo' => $demo,
                'gifKey' => $gifKey,
                'seconds' => $seconds,
            ];
        }

        return array_slice($result, 0, 6);
    }

    /**
     * @return array<int, array{title:string,description:string,demo:string,gifKey:string,seconds:int}>
     */
    private function buildYogaFallbackPlan(array $allowedDemos, array $allowedGifs): array
    {
        $catalog = [
            ['title' => 'Neck Release', 'description' => 'Tilt head gently side to side while breathing slowly.', 'demo' => 'neck'],
            ['title' => 'Shoulder Rolls', 'description' => 'Roll shoulders forward and backward to release tension.', 'demo' => 'shoulders'],
            ['title' => 'Seated Twist', 'description' => 'Sit tall and rotate softly to each side without force.', 'demo' => 'twist'],
            ['title' => 'Cat Cow', 'description' => 'Alternate arching and rounding your back with your breath.', 'demo' => 'catcow'],
            ['title' => 'Child Pose', 'description' => 'Relax hips back and stretch arms forward with calm breathing.', 'demo' => 'child'],
            ['title' => 'Side Stretch', 'description' => 'Reach one arm overhead and lengthen each side of the body.', 'demo' => 'side'],
            ['title' => 'Wrist Release', 'description' => 'Rotate wrists and extend fingers to ease hand tension.', 'demo' => 'wrist'],
            ['title' => 'Ankle Circles', 'description' => 'Lift one foot slightly and draw smooth circles with the ankle.', 'demo' => 'ankle'],
            ['title' => 'Breath Reset', 'description' => 'Inhale for 4 counts and exhale for 6 counts.', 'demo' => 'breath'],
        ];

        $catalog = array_values(array_filter($catalog, static fn (array $row): bool => in_array($row['demo'], $allowedDemos, true)));
        shuffle($catalog);
        $picked = array_slice($catalog, 0, 6);

        $plan = [];
        foreach (array_values($picked) as $idx => $row) {
            $gif = $allowedGifs[$idx % max(1, count($allowedGifs))] ?? 'gif1';
            $plan[] = [
                'title' => $row['title'],
                'description' => $row['description'],
                'demo' => $row['demo'],
                'gifKey' => $gif,
                'seconds' => 20,
            ];
        }

        return $plan;
    }
}
