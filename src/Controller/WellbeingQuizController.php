<?php

namespace App\Controller;

use App\Entity\QuizStress;
use App\Repository\QuestionStressRepository;
use App\Repository\QuizStressRepository;
use App\Repository\RecommendationStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
