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
    #[Route('', name: 'app_wellbeing_quiz', methods: ['GET'])]
    public function quiz(QuestionStressRepository $questionRepo): Response
    {
        $questions = $questionRepo->findBy(
            ['isActive' => true],
            ['questionNumber' => 'ASC']
        );

        return $this->render('pages/wellbeing/quiz.html.twig', [
            'questions' => $questions,
        ]);
    }

    #[Route('/submit', name: 'app_wellbeing_quiz_submit', methods: ['POST'])]
    public function submit(Request $request, QuestionStressRepository $questionRepo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('wellbeing_quiz', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $answers = $request->request->all('answers');
        $totalScore = array_sum($answers);
        
        // Determine stress level
        $stressLevel = 'low';
        $interpretation = '';
        
        if ($totalScore <= 10) {
            $stressLevel = 'minimal';
            $interpretation = 'Your stress levels appear to be minimal. Continue practicing good self-care habits and maintain your healthy routines.';
        } elseif ($totalScore <= 20) {
            $stressLevel = 'mild';
            $interpretation = 'You are experiencing mild stress. Consider incorporating some relaxation techniques and ensure you\'re taking regular breaks.';
        } elseif ($totalScore <= 30) {
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

        return $this->render('pages/wellbeing/quiz_results.html.twig', [
            'quiz' => $quiz,
            'recommendations' => $recommendations,
            'recLevel' => $recLevel,
        ]);
    }
}