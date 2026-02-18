<?php

namespace App\Controller;

use App\Entity\QuizStress;
use App\Repository\QuestionStressRepository;
<<<<<<< HEAD
use App\Repository\QuizStressRepository;
use App\Repository\RecommendationStressRepository;
=======
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

<<<<<<< HEAD
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
    public function quiz(QuestionStressRepository $questionRepo): Response
    {
        $questions = $questionRepo->findBy(
            ['isActive' => true],
            ['position' => 'ASC']   // ← LE PLUS IMPORTANT !
        );

        return $this->render('pages/wellbeing/quiz.html.twig', [
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

        $submittedAnswers = $request->request->all('answers');
        if (empty($submittedAnswers)) {
            $this->addFlash('error', 'Please answer at least one question.');
            return $this->redirectToRoute('app_wellbeing_quiz');
        }

        $questions = $questionRepo->findBy(['isActive' => true], ['position' => 'ASC']);
        $questionIds = array_map(static fn($q) => (string) $q->getId(), $questions);
        $validScores = array_keys(self::ANSWER_OPTIONS);

        $answers = [];
        $totalScore = 0;
        foreach ($questionIds as $questionId) {
            if (!array_key_exists($questionId, $submittedAnswers)) {
                $this->addFlash('error', 'Please answer all questions before submitting.');
                return $this->redirectToRoute('app_wellbeing_quiz');
            }

            $choiceScore = (int) $submittedAnswers[$questionId];
            if (!in_array($choiceScore, $validScores, true)) {
                $this->addFlash('error', 'Invalid answer detected. Please retry the quiz.');
                return $this->redirectToRoute('app_wellbeing_quiz');
            }

            // Each response is weighted by 10 as requested.
            $questionScore = $choiceScore * 10;
            $answers[(string) $questionId] = $questionScore;
            $totalScore += $questionScore;
        }

        $answeredCount = count($answers);
        $averageScore = $answeredCount > 0 ? ($totalScore / $answeredCount) : 10;

        // Determine stress level from average weighted score (10..40).
        $stressLevel = 'minimal';
        $interpretation = '';

        if ($averageScore <= 15) {
            $stressLevel = 'minimal';
            $interpretation = 'Your stress levels appear to be minimal. Continue practicing good self-care habits and maintain your healthy routines.';
        } elseif ($averageScore <= 25) {
            $stressLevel = 'mild';
            $interpretation = 'You are experiencing mild stress. Consider incorporating some relaxation techniques and ensure you\'re taking regular breaks.';
        } elseif ($averageScore <= 35) {
            $stressLevel = 'moderate';
            $interpretation = 'Your stress levels are moderate. It\'s important to prioritize self-care and consider using coping tools. If symptoms persist, consider speaking with a counselor.';
        } else {
            $stressLevel = 'high';
            $interpretation = 'You are experiencing high stress levels. Please prioritize your mental health and consider reaching out to a mental health professional for support.';
        }

        // Save quiz result
        $quiz = new QuizStress();
        $quiz->setQuizDate(new \DateTime());
        $quiz->setAnswers($answers);
        $quiz->setTotalScore($totalScore);
        $quiz->setStressLevel($stressLevel);
        $quiz->setInterpretation($interpretation);
        $quiz->setCreatedWithAi(false);
        $quiz->setCreatedAt(new \DateTime());

        $em->persist($quiz);
        $em->flush();

        $this->addFlash('success', 'Quiz submitted successfully!');

        return $this->redirectToRoute('app_wellbeing_quiz_results', ['id' => $quiz->getId()]);
    }

    #[Route('/results/{id}', name: 'app_wellbeing_quiz_results', methods: ['GET'])]
    public function results(QuizStress $quiz, RecommendationStressRepository $recRepo): Response
    {
        // Map stress level to recommendation level
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
}
=======
class WellbeingQuizController extends AbstractController
{
    #[Route('/wellbeing/quiz', name: 'app_wellbeing_quiz', methods: ['GET'])]
    public function quiz(QuestionStressRepository $questionRepo): Response
    {
        $questions = $questionRepo->findBy([], ['orderIndex' => 'ASC']);

        return $this->render('pages/wellbeing/quiz.html.twig', [
            'questions' => $questions,
        ]);
    }

    #[Route('/wellbeing/quiz/submit', name: 'app_wellbeing_quiz_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        QuestionStressRepository $questionRepo,
        EntityManagerInterface $em
    ): Response {
        // ────────────────────────────────────────────────
        // Fixed line – choose ONE of the three versions below
        // ────────────────────────────────────────────────

        // Best / most common today
        $answers = $request->request->all()['answers'] ?? [];

        // Alternative A – also correct
        // $answers = $request->request->get('answers') ?? [];

        // Alternative B – very explicit
        // $answers = $request->request->get('answers', null) ?? [];

        // ────────────────────────────────────────────────
        // Rest of your code remains the same
        // ────────────────────────────────────────────────

        $questions = $questionRepo->findBy([], ['orderIndex' => 'ASC']);

        if (empty($questions)) {
            $this->addFlash('error', 'No questions available at the moment.');
            return $this->redirectToRoute('app_wellbeing_quiz');
        }

        $score = 0;
        $answered = 0;

        foreach ($answers as $qId => $value) {
            $value = (int) $value;
            if ($value >= 1 && $value <= 5) {
                $score += $value;
                $answered++;
            }
        }

        if ($answered < count($questions)) {
            $this->addFlash('warning', 'Please answer all questions.');
            return $this->redirectToRoute('app_wellbeing_quiz');
        }

        $maxScore = count($questions) * 5;

        $quiz = new QuizStress();
        $quiz->setTitle('Stress Quiz - ' . date('d M Y H:i'));
        $quiz->setScore($score);
        $quiz->setAnswers($answers);
        $em->persist($quiz);
        $em->flush();

        // Level + message + advice (English)
        if ($score <= 10) {
            $level = 'Low';
            $message = "Your stress level appears to be low.";
            $advice = "You're doing well! Keep up healthy habits like regular exercise, good sleep, and time for activities you enjoy.";
        } elseif ($score <= 20) {
            $level = 'Moderate';
            $message = "Your stress level is moderate.";
            $advice = "Try incorporating relaxation techniques: deep breathing, short walks, meditation, journaling, or talking to someone you trust.";
        } else {
            $level = 'High';
            $message = "Your stress level appears to be high.";
            $advice = "It would be beneficial to talk to a trusted person or a professional (counselor, psychologist, or doctor). You don't have to face this alone.";
        }

        return $this->render('pages/wellbeing/quiz_result.html.twig', [
            'score'    => $score,
            'maxScore' => $maxScore,
            'level'    => $level,
            'message'  => $message,
            'advice'   => $advice,
        ]);
    }
}
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
