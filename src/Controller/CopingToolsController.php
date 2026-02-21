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
<<<<<<< HEAD
    public function index(CopingSessionRepository $sessionRepo): Response
    {
        $user = $this->getUser();
        $recentSessions = [];
        
        if ($user) {
            $recentSessions = $sessionRepo->findBy(
                ['user' => $user],
                ['createdAt' => 'DESC'],
                5
            );
        }
        
        // Calculate stats
        $totalSessions = count($recentSessions);
        $totalMinutes = 0;
        $completedSessions = 0;
        
        foreach ($recentSessions as $session) {
            if ($session->getActualSeconds()) {
                $totalMinutes += $session->getActualSeconds() / 60;
            }
            if ($session->getStatus() === 'finished') {
                $completedSessions++;
            }
        }

        return $this->render('pages/wellbeing/tools.html.twig', [
            'tools' => SampleData::getCopingTools(),
            'recent_sessions' => $recentSessions,
            'total_sessions' => $totalSessions,
            'total_minutes' => round($totalMinutes),
            'completed_sessions' => $completedSessions,
=======
    public function index(): Response
    {
        return $this->render('pages/wellbeing/tools.html.twig', [
            'tools' => SampleData::getCopingTools(),
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
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
<<<<<<< HEAD
        $session->setStartedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $session->setStatus('started');
        $session->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
=======
        $session->setStartedAt(new \DateTime());
        $session->setStatus('started');
        $session->setCreatedAt(new \DateTime());
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd

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
<<<<<<< HEAD
            $startedAt = \DateTime::createFromFormat('Y-m-d H:i:s', $session->getStartedAt());
            if ($startedAt) {
                $actualSeconds = max(1, $finish->getTimestamp() - $startedAt->getTimestamp());
            }
        }

        $session->setFinishedAt($finish->format('Y-m-d H:i:s'));
        $session->setActualSeconds($actualSeconds);
        $session->setStatus($status);
        $session->setUpdatedAt($finish->format('Y-m-d H:i:s'));
=======
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
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd

        $em->flush();

        return $this->json([
            'ok' => true,
            'status' => $session->getStatus(),
            'actualSeconds' => $session->getActualSeconds(),
        ]);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
