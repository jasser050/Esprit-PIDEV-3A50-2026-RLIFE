<?php

namespace App\Controller\Api;

use App\Service\WellbeingAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WellbeingMotivationController extends AbstractController
{
    #[Route('/api/wellbeing/motivation/quote', name: 'app_wellbeing_motivation_quote', methods: ['GET'])]
    public function quote(Request $request, WellbeingAiService $wellbeingAiService): JsonResponse
    {
        $type = (string) $request->query->get('type', 'motivation');
        $result = $wellbeingAiService->generateMotivationQuote($type);

        return $this->json([
            'ok' => true,
            'quote' => $result['quote'] ?? 'Keep going.',
            'source' => $result['source'] ?? 'fallback',
            'type' => $result['type'] ?? 'motivation',
            'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i'),
        ]);
    }
}
