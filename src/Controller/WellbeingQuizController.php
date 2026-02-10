<?php

namespace App\Controller;

use App\Repository\QuestionStressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WellbeingQuizController extends AbstractController
{
    #[Route('/wellbeing/quiz', name: 'app_wellbeing_quiz', methods: ['GET'])]
    public function quiz(QuestionStressRepository $questionRepo): Response
    {
        $questions = $questionRepo->findAll();

        return $this->render('pages/wellbeing/quiz.html.twig', [
            'questions' => $questions,
        ]);
    }
}
