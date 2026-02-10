<?php

namespace App\Controller\Api;

use App\Entity\QuizStress;
use App\Entity\RecommendationStress;
use App\Repository\QuizStressRepository;
use App\Repository\QuestionStressRepository;
use App\Service\StressAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StressQuizApiController extends AbstractController
{
    #[Route('/api/stress-quiz/{id}/submit', name: 'api_stress_quiz_submit', methods: ['POST'])]
    public function submit(
        int $id,
        Request $request,
        QuizStressRepository $quizRepo,
        QuestionStressRepository $questionRepo,
        StressAiService $ai,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $quiz = $quizRepo->find($id);
        if (!$quiz) {
            // si tu n’utilises pas une table “Quiz template”, tu peux créer un QuizStress à la volée
            $quiz = new QuizStress();
            $quiz->setUser($this->getUser());
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $answers = $payload['answers'] ?? [];

        // Calcul score (0-4 * 10 questions = /40)
        $score = 0;
        foreach ($answers as $questionId => $value) {
            $score += max(0, min(4, (int)$value));
        }

        $quiz->setAnswers($answers);
        $quiz->setScore($score);
        $quiz->setCreatedAt(new \DateTimeImmutable());

        $em->persist($quiz);

        // IA -> recommendation texte
        $recText = $ai->generateRecommendationText($score);

        $rec = new RecommendationStress();
        $rec->setUser($this->getUser());
        $rec->setQuiz($quiz);
        $rec->setContent($recText);
        $rec->setCreatedAt(new \DateTimeImmutable());

        $em->persist($rec);
        $em->flush();

        return $this->json([
            'ok' => true,
            'score' => $score,
            'recommendation' => $recText,
        ]);
    }
}
