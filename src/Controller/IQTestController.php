<?php

namespace App\Controller;

use App\Service\IQTestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/iq-test', name: 'iq_test_')]
class IQTestController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/start', name: 'start')]
    public function start(SessionInterface $session): Response
    {
        // Clear any existing test data when starting fresh
        $session->remove('iq_test_questions');
        $session->remove('iq_test_start_time');
        $session->remove('iq_test_answers');
        
        $this->logger->info('IQ Test: Start page loaded, session cleared');
        
        return $this->render('pages/courses/iq_start.html.twig');
    }

    #[Route('/', name: 'index')]
    #[Route('/test', name: 'test')]
    public function index(IQTestService $iqTestService, SessionInterface $session): Response
    {
        try {
            // Generate fresh questions each time
            $questions = $iqTestService->generateIQTest(15);
            
            $this->logger->info('IQ Test: Generated questions', [
                'count' => count($questions),
                'session_id' => $session->getId()
            ]);

            // Store in session
            $session->set('iq_test_questions', $questions);
            $session->set('iq_test_start_time', time());
            $session->remove('iq_test_answers');
            
            // Verify session data was saved
            $savedQuestions = $session->get('iq_test_questions');
            $savedStartTime = $session->get('iq_test_start_time');
            
            $this->logger->info('IQ Test: Session data saved', [
                'questions_saved' => count($savedQuestions ?? []),
                'start_time_saved' => $savedStartTime,
                'session_id' => $session->getId()
            ]);

            return $this->render('pages/courses/iq_test.html.twig', [
                'questions' => $questions,
                'totalQuestions' => count($questions),
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('IQ Test: Error generating test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'Error generating test: ' . $e->getMessage());
            return $this->redirectToRoute('iq_test_start');
        }
    }

    #[Route('/submit', name: 'submit', methods: ['POST'])]
    public function submit(Request $request, IQTestService $iqTestService, SessionInterface $session): Response
    {
        $this->logger->info('IQ Test: Submit called', [
            'session_id' => $session->getId(),
            'method' => $request->getMethod()
        ]);
        
        // Get questions and start time from session
        $questions = $session->get('iq_test_questions');
        $startTime = $session->get('iq_test_start_time');

        $this->logger->info('IQ Test: Session data retrieved', [
            'has_questions' => !empty($questions),
            'questions_count' => count($questions ?? []),
            'has_start_time' => !empty($startTime),
            'start_time' => $startTime
        ]);

        // Validate session data
        if (!$questions || !is_array($questions) || count($questions) === 0) {
            $this->logger->warning('IQ Test: No questions found in session');
            $this->addFlash('error', 'No active test found. Please start a new test.');
            return $this->redirectToRoute('iq_test_start');
        }
        
        if (!$startTime) {
            $this->logger->warning('IQ Test: No start time found in session');
            $this->addFlash('error', 'No active test found. Please start a new test.');
            return $this->redirectToRoute('iq_test_start');
        }

        // Get user answers from POST data
        $postData = $request->request->all();
        $answers = $postData['answers'] ?? [];

        $this->logger->info('IQ Test: Answers received', [
            'answers_count' => count($answers),
            'post_data_keys' => array_keys($postData)
        ]);

        // Calculate results
        $correctAnswers = 0;
        $detailedResults = [];

        foreach ($questions as $question) {
            $questionId = (string)$question['id'];
            $userAnswer = $answers[$questionId] ?? null;
            $isCorrect = ($userAnswer === $question['correct_answer']);

            if ($isCorrect) {
                $correctAnswers++;
            }

            $detailedResults[] = [
                'question' => $question['question'],
                'user_answer' => $userAnswer,
                'correct_answer' => $question['correct_answer'],
                'is_correct' => $isCorrect,
                'category' => $question['category'],
                'difficulty' => $question['difficulty'],
                'options' => $question['options']
            ];
        }

        // Calculate time spent
        $timeSpent = time() - $startTime;

        $this->logger->info('IQ Test: Results calculated', [
            'correct_answers' => $correctAnswers,
            'total_questions' => count($questions),
            'time_spent' => $timeSpent
        ]);

        // Calculate IQ score
        $result = $iqTestService->calculateIQScore(
            $correctAnswers,
            count($questions),
            $timeSpent
        );

        $this->logger->info('IQ Test: IQ score calculated', [
            'iq_score' => $result['iq_score'],
            'level' => $result['level']
        ]);

        // Clear session data
        $session->remove('iq_test_questions');
        $session->remove('iq_test_start_time');
        $session->remove('iq_test_answers');

        // Render results page
        return $this->render('pages/courses/iq_results.html.twig', [
            'result' => $result,
            'detailedResults' => $detailedResults,
        ]);
    }

    #[Route('/api/test', name: 'api_test', methods: ['GET'])]
    public function apiTest(IQTestService $iqTestService, SessionInterface $session): JsonResponse
    {
        try {
            // Test the service
            $questions = $iqTestService->generateIQTest(5);
            
            // Check session
            $sessionQuestions = $session->get('iq_test_questions');
            $sessionStartTime = $session->get('iq_test_start_time');
            
            return $this->json([
                'success' => true,
                'message' => 'IQ Test API is working!',
                'questions_generated' => count($questions),
                'sample_question' => $questions[0] ?? null,
                'session_info' => [
                    'session_id' => $session->getId(),
                    'has_questions_in_session' => !empty($sessionQuestions),
                    'questions_in_session_count' => count($sessionQuestions ?? []),
                    'has_start_time' => !empty($sessionStartTime),
                    'start_time' => $sessionStartTime
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/retry', name: 'retry')]
    public function retry(SessionInterface $session): Response
    {
        // Clear any existing test data
        $session->remove('iq_test_questions');
        $session->remove('iq_test_start_time');
        $session->remove('iq_test_answers');

        $this->logger->info('IQ Test: Retry - session cleared');

        // Redirect to test page (will generate new questions)
        return $this->redirectToRoute('iq_test_index');
    }
    
    #[Route('/debug', name: 'debug', methods: ['GET'])]
    public function debug(SessionInterface $session): JsonResponse
    {
        $questions = $session->get('iq_test_questions');
        $startTime = $session->get('iq_test_start_time');
        
        return $this->json([
            'session_id' => $session->getId(),
            'has_questions' => !empty($questions),
            'questions_count' => count($questions ?? []),
            'first_question' => $questions[0] ?? null,
            'has_start_time' => !empty($startTime),
            'start_time' => $startTime,
            'current_time' => time(),
            'all_session_keys' => array_keys($session->all())
        ]);
    }
}
