<?php

namespace App\Controller;

use App\Entity\QuizStress;
use App\Repository\QuestionStressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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