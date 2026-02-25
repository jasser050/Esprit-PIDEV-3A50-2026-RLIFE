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
        $languageInstruction = $this->languageInstructionFromCode($languageCode);

        $intent = $this->detectWellbeingIntent($message);

        $messages = [[
            'role' => 'system',
            'content' => <<<PROMPT
You are RLIFE Wellbeing AI Coach for students.
Rules:
- Be warm, practical, and supportive.
- Reply ONLY in {$languageInstruction}.
- Use the student's last message explicitly and respond to it directly.
- Avoid generic templates or repeated openings.
- Give concise advice with concrete next steps (2-5 bullet points max).
- Ask at most ONE clarifying question, only if needed.
- If student asks motivation, provide encouraging and realistic guidance.
- If user mentions self-harm, suicide, or danger, respond with empathy and strongly suggest immediate local emergency/crisis help.
- Do not pretend to be a doctor, but provide safe wellbeing guidance.
Context:
- Detected intent: {$intent}
- Last message: "{$message}"
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
            'temperature' => 0.45,
            'max_tokens' => 700,
        ]);

        if ($reply === null || trim($reply) === '') {
            return $this->json([
                'ok' => true,
                'reply' => $this->buildLocalizedFallbackReply($message, $languageCode, $intent),
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

        if (!$this->isReplyRelevant($cleanReply, $intent)) {
            $cleanReply = $this->buildLocalizedFallbackReply($message, $languageCode, $intent);
        }

        return $this->json([
            'ok' => true,
            'reply' => $cleanReply,
            'source' => 'ai',
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

    private function buildLocalizedFallbackReply(string $message, string $languageCode, string $intent = 'general'): string
    {
        $text = function_exists('mb_strtolower') ? mb_strtolower($message) : strtolower($message);
        $isRisk = $this->isHighRiskMessage($text);
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
            if ($isPhysical) {
                return 'Je suis la pour toi. Si tu te sens malade, commence par te reposer, boire de l eau et surveiller tes symptomes. Si la douleur est forte, s aggrave ou dure, consulte rapidement un medecin ou les urgences. Dis-moi ce que tu ressens exactement et je t aide a organiser les prochaines etapes.';
            }
            if ($intent === 'sleep') {
                return "D'accord. Pour mieux dormir ce soir, essaie ceci :\n- Coupe les ecrans 30-60 min avant de dormir.\n- Fais 4-7-8 respiration pendant 3 minutes.\n- Note 2-3 pensees qui tournent et planifie demain.\n- Chambre fraiche et sombre.\nVeux-tu un plan rapide de 7 jours pour stabiliser ton sommeil ?";
            }
            if ($intent === 'stress') {
                return "On va calmer le stress maintenant :\n- Respire 4-4-6 pendant 2 minutes.\n- Fais une liste des 3 priorites du jour.\n- Choisis une action de 10 minutes.\nDis-moi ce qui te stresse le plus pour te donner un plan simple.";
            }
            if ($intent === 'focus') {
                return "Pour te concentrer :\n- 25 min focus + 5 min pause (pomodoro).\n- Telephone hors de vue.\n- Une seule tache claire.\nQu'est-ce que tu dois faire en premier ?";
            }
            if ($intent === 'motivation') {
                return "On peut relancer la motivation :\n- Un objectif tres petit (5-10 minutes).\n- Chrono court.\n- Recompense simple apres.\nQu'est-ce que tu aimerais accomplir aujourd'hui ?";
            }
            $samples = [
                'Merci de m avoir ecrit. Je suis avec toi. Dis-moi ton probleme principal en une phrase, et on avance etape par etape.',
                'Je te comprends. On va faire simple: 1) respirer 30 secondes, 2) definir le probleme numero 1, 3) choisir une petite action maintenant.',
                'Tu n es pas seul(e). Dis-moi ce qui te stresse le plus en ce moment, et je te donne un plan court et concret.',
            ];
            return $samples[array_rand($samples)];
        }

        if ($languageCode === 'ar-SA' || $languageCode === 'ar-TN') {
            if ($isRisk) {
                return 'Ana ma3ak. Salamtak awwalan. Itha kont fi khatar al-an, ittasel bitawari2 fi baladek fawran, w khalli shakhss thiqa ykoun ma3ak. Najem n3awnek khatwa b khatwa.';
            }
            if ($intent === 'sleep') {
                return "Bch t3awn rohek 3al noum:\n- b3ed 3an ecrans 30-60 min.\n- 4-7-8 respiration 3 minutes.\n- ekteb aham 2-3 afkar w khalliha l-ghodwa.\n- ghurfa barda w mdhllma.\nT7eb plan sghir l-7week ?";
            }
            if ($intent === 'stress') {
                return "Bch nhawlou n9allou stress tawa:\n- 4-4-6 respiration 2 min.\n- 3 priorites lyoum.\n- ekhtar khotwa sghira 10 min.\nChnoua akther haja mdhayqtek ?";
            }
            if ($intent === 'focus') {
                return "Bch t7assen l-focus:\n- 25 min khidma + 5 min raha.\n- telifoun barra 3la nadhar.\n- 7aja we7da wadha.\nChnoua awwel haja bch tabda biha ?";
            }
            if ($intent === 'motivation') {
                return "Bch n7arkou el-motivation:\n- objectif sghir 5-10 min.\n- chrono 10 min.\n- recompense sghira ba3d.\nChnoua t7eb tousel lih lyoum ?";
            }
            return 'Ana ma3ak. Ahki li chnouwa akther haja mdhayqtek taw, w n3tik khotwa sghira tabda beha taw.';
        }

        if ($isRisk) {
            return 'I am really sorry you are going through this. Your safety comes first. If you are in immediate danger, call local emergency services now and contact someone you trust nearby. I can stay with you and help step by step.';
        }

        if ($isPhysical) {
            return 'I am here with you. If you feel sick, rest, hydrate, and monitor symptoms. If pain is strong or getting worse, seek urgent medical care. Tell me your exact symptoms and I will help you plan next steps.';
        }

        if ($intent === 'sleep') {
            return "Let’s improve sleep tonight:\n- No screens 30–60 min before bed.\n- Do 4-7-8 breathing for 3 minutes.\n- Write 2–3 worries to park them for tomorrow.\n- Keep the room cool and dark.\nWant a simple 7‑day sleep reset plan?";
        }
        if ($intent === 'stress') {
            return "Let’s reduce stress now:\n- Breathe 4-4-6 for 2 minutes.\n- List the top 3 priorities.\n- Pick one 10‑minute action.\nWhat’s the biggest stressor right now?";
        }
        if ($intent === 'focus') {
            return "For focus:\n- 25 min focus + 5 min break (Pomodoro).\n- Phone out of sight.\n- One clear task only.\nWhat should we start with?";
        }
        if ($intent === 'motivation') {
            return "To boost motivation:\n- Set a tiny goal (5–10 min).\n- Start a short timer.\n- Reward yourself after.\nWhat do you want to get done today?";
        }

        $samples = [
            'I am here for you. Tell me your main issue in one sentence, and we will solve it step by step.',
            'You are not alone. We can do this calmly: identify the top problem, then pick one small action now.',
            'Thanks for sharing this. Tell me what feels hardest right now, and I will give you a simple plan.',
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

    private function detectWellbeingIntent(string $message): string
    {
        $text = function_exists('mb_strtolower') ? mb_strtolower($message) : strtolower($message);
        $intents = [
            'sleep' => ['sleep', 'insomnia', 'can\'t sleep', 'need to sleep', 'fatigue', 'tired', 'sommeil', 'insomnie', 'dormir', 'fatigue', 'taab', 'n3as', 'n3ess', 'n3ass', 'ma n9drch n3ess'],
            'stress' => ['stress', 'stressed', 'overwhelmed', 'anxious', 'anxiety', 'panic', 'stressé', 'stressée', 'angoisse', 'panique', 'قلق', 'توتر'],
            'motivation' => ['motivation', 'motivated', 'motiver', 'boost', 'discouraged', 'demotivated', 'malmotiv', 'coeur', 'heart'],
            'focus' => ['focus', 'concentrate', 'concentration', 'distracted', 'study', 'revision', 'révision', 'concentration', 'تركز', 'study', 'exam', 'exams'],
            'sadness' => ['sad', 'down', 'depressed', 'depression', 'hopeless', 'triste', 'déprimé', 'deprime', 'حزين', 'مكتئب'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    private function isReplyRelevant(string $reply, string $intent): bool
    {
        if ($intent === 'general') {
            return true;
        }
        $text = function_exists('mb_strtolower') ? mb_strtolower($reply) : strtolower($reply);
        $keywords = match ($intent) {
            'sleep' => ['sleep', 'bed', 'insomnia', 'sommeil', 'dormir', 'نوم', 'نعاس'],
            'stress' => ['stress', 'anxiety', 'calm', 'stressé', 'angoisse', 'قلق', 'توتر'],
            'focus' => ['focus', 'concentration', 'study', 'revision', 'concentrer', 'تركيز'],
            'motivation' => ['motivation', 'encourage', 'goal', 'objectif', 'تحفيز'],
            'sadness' => ['sad', 'down', 'depressed', 'triste', 'حزين'],
            default => [],
        };
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
