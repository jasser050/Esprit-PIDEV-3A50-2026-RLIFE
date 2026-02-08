<?php

namespace App\Controller;

use App\Data\SampleData;
use App\Entity\CopingSession;
use App\Repository\CopingSessionRepository;
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
    public function index(): Response
    {
        return $this->render('pages/wellbeing/tools.html.twig', [
            'tools' => SampleData::getCopingTools(),
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
        $session->setStartedAt(new \DateTime());
        $session->setStatus('started');
        $session->setCreatedAt(new \DateTime());

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
            $actualSeconds = max(1, $finish->getTimestamp() - $session->getStartedAt()->getTimestamp());
        }

        // Notes (Gratitude, etc.) - optionnel
        $notes = (string)($data['notes'] ?? '');
        if ($notes !== '' && method_exists($session, 'setNotes')) {
            $session->setNotes($notes);
        }

        $session->setFinishedAt($finish);
        $session->setActualSeconds($actualSeconds);
        $session->setStatus($status);
        $session->setUpdatedAt(new \DateTime());

        $em->flush();

        return $this->json([
            'ok' => true,
            'status' => $session->getStatus(),
            'actualSeconds' => $session->getActualSeconds(),
        ]);
    }
}
