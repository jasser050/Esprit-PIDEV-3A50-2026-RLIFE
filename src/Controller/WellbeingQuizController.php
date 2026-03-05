<?php

namespace App\Controller;

use App\Entity\QuizStress;
use App\Repository\QuestionStressRepository;
use App\Repository\RecommendationStressRepository;
use App\Service\StressAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/wellbeing/quiz')]
class WellbeingQuizController extends AbstractController
{
    private const ANSWER_OPTIONS = [
        1 => 'Not at all',
        2 => 'A little',
        3 => 'Moderately',
        4 => 'Very much',
    ];

    #[Route('', name: 'app_wellbeing_quiz', methods: ['GET'])]
    public function quiz(Request $request, QuestionStressRepository $questionRepo): Response
    {
        $mode = strtolower((string) $request->query->get('mode', ''));
        if (!in_array($mode, ['simple', 'ai'], true)) {
            return $this->render('pages/wellbeing/quiz.html.twig', [
                'quizMode' => null,
                'questions' => [],
                'answerOptions' => self::ANSWER_OPTIONS,
            ]);
        }

        if ($mode === 'ai') {
            $questions = $this->generateAiQuestions();
            $request->getSession()->set('wellbeing_quiz_ai_questions', $questions);
        } else {
            $questions = $questionRepo->findBy(
                ['isActive' => true],
                ['position' => 'ASC']
            );
        }

        return $this->render('pages/wellbeing/quiz.html.twig', [
            'quizMode' => $mode,
            'questions' => $questions,
            'answerOptions' => self::ANSWER_OPTIONS,
        ]);
    }

    #[Route('/submit', name: 'app_wellbeing_quiz_submit', methods: ['POST'])]
    public function submit(Request $request, QuestionStressRepository $questionRepo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('wellbeing_quiz', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $quizMode = strtolower((string) $request->request->get('quiz_mode', 'simple'));
        if (!in_array($quizMode, ['simple', 'ai'], true)) {
            $quizMode = 'simple';
        }

        $submittedAnswers = $request->request->all('answers');
        if (empty($submittedAnswers)) {
            $this->addFlash('error', 'Please answer at least one question.');
            return $this->redirectToRoute('app_wellbeing_quiz', ['mode' => $quizMode]);
        }

        if ($quizMode === 'ai') {
            $questions = $request->getSession()->get('wellbeing_quiz_ai_questions', []);
            if (!is_array($questions) || count($questions) === 0) {
                $this->addFlash('error', 'AI quiz expired. Please start again.');
                return $this->redirectToRoute('app_wellbeing_quiz', ['mode' => 'ai']);
            }
            $questionIds = array_map(static fn(array $q) => (string) ($q['id'] ?? ''), $questions);
            $questionIds = array_values(array_filter($questionIds, static fn(string $id) => $id !== ''));
        } else {
            $questions = $questionRepo->findBy(['isActive' => true], ['position' => 'ASC']);
            $questionIds = array_map(static fn($q) => (string) $q->getId(), $questions);
        }

        $validScores = array_keys(self::ANSWER_OPTIONS);

        $answers = [];
        $totalScore = 0;
        foreach ($questionIds as $questionId) {
            if (!array_key_exists($questionId, $submittedAnswers)) {
                $this->addFlash('error', 'Please answer all questions before submitting.');
                return $this->redirectToRoute('app_wellbeing_quiz', [
                    'mode' => $quizMode,
                    'missing' => $questionId,
                ]);
            }

            $choiceScore = (int) $submittedAnswers[$questionId];
            if (!in_array($choiceScore, $validScores, true)) {
                $this->addFlash('error', 'Invalid answer detected. Please retry the quiz.');
                return $this->redirectToRoute('app_wellbeing_quiz', ['mode' => $quizMode]);
            }

            $questionScore = $choiceScore * 10;
            $answers[(string) $questionId] = $questionScore;
            $totalScore += $questionScore;
        }

        $answeredCount = count($answers);
        $averageScore = $answeredCount > 0 ? ($totalScore / $answeredCount) : 10;

        $stressLevel = 'minimal';
        $interpretation = '';

        if ($averageScore <= 15) {
            $stressLevel = 'minimal';
            $interpretation = 'Your stress levels appear to be minimal. Continue practicing good self-care habits and maintain your healthy routines.';
        } elseif ($averageScore <= 25) {
            $stressLevel = 'mild';
            $interpretation = 'You are experiencing mild stress. Consider incorporating some relaxation techniques and ensure you are taking regular breaks.';
        } elseif ($averageScore <= 35) {
            $stressLevel = 'moderate';
            $interpretation = 'Your stress levels are moderate. It is important to prioritize self-care and consider using coping tools. If symptoms persist, consider speaking with a counselor.';
        } else {
            $stressLevel = 'high';
            $interpretation = 'You are experiencing high stress levels. Please prioritize your mental health and consider reaching out to a mental health professional for support.';
        }

        $quiz = new QuizStress();
        $quiz->setQuizDate(new \DateTime());
        $quiz->setAnswers($answers);
        $quiz->setTotalScore($totalScore);
        $quiz->setStressLevel($stressLevel);
        $quiz->setInterpretation($interpretation);
        $quiz->setCreatedWithAi($quizMode === 'ai');
        if ($quizMode === 'ai') {
            $quiz->setAiModel('local-randomized-v1');
            $quiz->setAiPromptVersion('wellbeing-quiz-ai-questions-v1');
            $request->getSession()->remove('wellbeing_quiz_ai_questions');
        }
        $quiz->setCreatedAt(new \DateTime());

        $em->persist($quiz);
        $em->flush();

        $this->addFlash('success', 'Quiz submitted successfully!');

        return $this->redirectToRoute('app_wellbeing_quiz_results', ['id' => $quiz->getId()]);
    }

    #[Route('/results/{id}', name: 'app_wellbeing_quiz_results', methods: ['GET'])]
    public function results(QuizStress $quiz, RecommendationStressRepository $recRepo): Response
    {
        $levelMap = [
            'minimal' => 'low',
            'mild' => 'low',
            'moderate' => 'medium',
            'high' => 'high',
        ];

        $recLevel = $levelMap[$quiz->getStressLevel()] ?? 'low';
        $recommendations = $recRepo->findByLevel($recLevel);
        $answersCount = count($quiz->getAnswers());
        $maxScore = max(40, $answersCount * 40);
        $scorePercent = (int) round(($quiz->getTotalScore() / $maxScore) * 100);
        if ($scorePercent > 100) {
            $scorePercent = 100;
        }

        return $this->render('pages/wellbeing/quiz_results.html.twig', [
            'quiz' => $quiz,
            'recommendations' => $recommendations,
            'recLevel' => $recLevel,
            'maxScore' => $maxScore,
            'scorePercent' => $scorePercent,
        ]);
    }

    #[Route('/results/{id}/ai-suggestions', name: 'app_wellbeing_quiz_ai_suggestions', methods: ['GET'])]
    public function aiSuggestions(Request $request, QuizStress $quiz, StressAiService $stressAiService): JsonResponse
    {
        $ai = $stressAiService->generate((int) $quiz->getTotalScore());
        $level = (string) ($ai['level'] ?? 'medium');
        $actions = array_values(array_filter(array_map('strval', (array) ($ai['actions'] ?? []))));

        $actionTitles = [
            'breathing' => 'Breathing Reset',
            'meditation' => 'Mini Meditation',
            'sleep' => 'Sleep Recovery',
            'boundaries' => 'Healthy Boundaries',
            'pomodoro' => 'Focus Sprint',
            'hydration' => 'Hydration Boost',
            'breaks' => 'Active Break',
            'gratitude' => 'Gratitude Note',
        ];

        $actionTips = [
            'breathing' => [
                'Try 4-7-8 breathing for 3 rounds before studying.',
                'Inhale 4s, exhale 6s for 2 minutes to lower tension.',
                'Take one deep breath each time you switch tasks.',
            ],
            'meditation' => [
                'Do a 5-minute body scan to relax your mind.',
                'Close your eyes and observe breathing for 3 minutes.',
                'Use a short mindfulness break between classes.',
            ],
            'sleep' => [
                'Set a fixed sleep time tonight and reduce screen light.',
                'Stop caffeine late in the day to improve sleep quality.',
                'Create a calm 20-minute wind-down before bed.',
            ],
            'boundaries' => [
                'Say no to one non-essential task today.',
                'Protect one uninterrupted study block in your schedule.',
                'Set a clear stop time for work tonight.',
            ],
            'pomodoro' => [
                'Try 25 minutes focus + 5 minutes break for 3 cycles.',
                'Set one micro-goal per focus block to reduce pressure.',
                'Pause after each cycle to stretch and reset.',
            ],
            'hydration' => [
                'Drink one glass of water now, then every study break.',
                'Keep a water bottle visible during your sessions.',
                'Pair each completed task with a hydration reminder.',
            ],
            'breaks' => [
                'Walk for 3-5 minutes between study blocks.',
                'Do neck and shoulder release every hour.',
                'Stand up and stretch before starting a new topic.',
            ],
            'gratitude' => [
                'Write one thing you handled well today.',
                'List two small wins before ending the day.',
                'Add a short gratitude sentence after each study session.',
            ],
        ];

        $defaultActionsByLevel = [
            'high' => ['breathing', 'meditation', 'sleep', 'boundaries'],
            'medium' => ['pomodoro', 'breaks', 'hydration', 'breathing'],
            'low' => ['gratitude', 'sleep', 'hydration', 'breaks'],
        ];

        $levelActions = $defaultActionsByLevel[$level] ?? $defaultActionsByLevel['medium'];
        $candidateActions = array_values(array_unique(array_merge($actions, $levelActions, array_keys($actionTips))));
        if ($candidateActions === []) {
            $candidateActions = ['breathing', 'breaks', 'gratitude'];
        }

        $session = $request->getSession();
        $sessionKey = sprintf('wellbeing_ai_last_suggestions_%d', (int) $quiz->getId());
        $lastFingerprint = (string) $session->get($sessionKey, '');

        $items = [];
        $fingerprint = '';
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $pool = $candidateActions;
            shuffle($pool);
            $chosenActions = array_slice($pool, 0, min(3, count($pool)));

            $items = [];
            foreach ($chosenActions as $action) {
                $tips = $actionTips[$action] ?? ['Take one small step now and keep it consistent today.'];
                shuffle($tips);
                $items[] = [
                    'title' => $actionTitles[$action] ?? ucfirst(str_replace('_', ' ', $action)),
                    'content' => $tips[0],
                ];
            }

            if ($items === []) {
                continue;
            }

            $fingerprint = md5(json_encode($items));
            if ($fingerprint !== $lastFingerprint) {
                break;
            }
        }

        if ($items === []) {
            $items[] = [
                'title' => 'Small Daily Reset',
                'content' => 'Take a short breathing pause, hydrate, and plan your next 2 study tasks.',
            ];
            $fingerprint = md5(json_encode($items));
        }
        $session->set($sessionKey, $fingerprint);

        $messageVariants = [
            'Here are fresh AI recommendations for your current stress profile.',
            'New AI suggestions generated. Try the ones that feel easiest today.',
            'AI refreshed your plan. Pick one action now, then another later.',
            'Updated AI guidance is ready. Start with the quickest step.',
        ];
        shuffle($messageVariants);

        return $this->json([
            'ok' => true,
            'level' => $level,
            'title' => $ai['title'] ?? 'AI suggestions',
            'message' => $messageVariants[0],
            'recommendations' => $items,
        ]);
    }

    /**
     * @return array<int, array{id:string, position:int, questionText:string}>
     */
    private function generateAiQuestions(): array
    {
        $questionPool = [
            'In the last 7 days, how often did you feel mentally overwhelmed by study tasks?',
            'How difficult was it to stay focused during your study sessions this week?',
            'How often did you feel physically tense (neck, shoulders, jaw) while studying?',
            'How much did stress affect your sleep quality recently?',
            'How often did you feel emotionally drained after classes or assignments?',
            'How hard was it to calm yourself after a stressful academic moment?',
            'How often did you postpone tasks because stress felt too high?',
            'How supported did you feel by your routine (breaks, food, sleep, movement)?',
            'How often did your thoughts race when you tried to rest?',
            'How much did stress reduce your motivation to study effectively?',
            'How difficult was it to manage stress before deadlines this week?',
            'How often did you feel anxious without a clear reason during the day?',
            'How much did stress affect your confidence in your academic abilities?',
            'How often did you struggle to recover energy after a long day of studying?',
            'How hard was it to keep a healthy balance between study and personal time?',
            'How often did you notice rapid breathing or heartbeat when stressed?',
            'How difficult was it to complete tasks without feeling pressure or panic?',
            'How often did you feel irritable because of accumulated stress?',
        ];

        shuffle($questionPool);
        $selected = array_slice($questionPool, 0, 10);

        $questions = [];
        foreach ($selected as $index => $text) {
            $questions[] = [
                'id' => 'ai_' . ($index + 1) . '_' . bin2hex(random_bytes(2)),
                'position' => $index + 1,
                'questionText' => $text,
            ];
        }

        return $questions;
    }
}
