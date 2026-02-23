<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\CoinTransaction;
use App\Entity\Project;
use App\Repository\AssignmentRepository;
use App\Repository\ProjectRepository;
use App\Repository\StudentGamificationRepository;
use App\Service\NotificationManager;
use App\Service\ProductivityAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai/productivity')]
#[IsGranted('ROLE_USER')]
class AiProductivityController extends AbstractController
{
    #[Route('/generate', name: 'app_ai_productivity_generate', methods: ['POST'])]
    public function generate(
        Request $request,
        AssignmentRepository $assignmentRepository,
        ProjectRepository $projectRepository,
        StudentGamificationRepository $gamificationRepository,
        ProductivityAiService $aiService
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('generate_ai_plan', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid AI generation request.');
            return $this->redirectBack($request);
        }

        $user = $this->getUser();
        $level = $gamificationRepository->findByUser($user)?->getLevel() ?? 1;

        $assignments = $assignmentRepository->findByUserWithFilters($user, 'dateFin', 'ASC');
        $projects = $projectRepository->findByUserWithFilters($user, 'dateFin', 'ASC');

        $plan = $aiService->generateProductivityPlan($user, $assignments, $projects, $level);
        $plan['generated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $request->getSession()->set('ai_productivity_plan', $plan);
        $this->addFlash('success', sprintf('AI recommendations generated (%s).', strtoupper((string) ($plan['source'] ?? 'fallback'))));

        return $this->redirectBack($request);
    }

    #[Route('/accept', name: 'app_ai_productivity_accept', methods: ['POST'])]
    public function accept(
        Request $request,
        AssignmentRepository $assignmentRepository,
        ProjectRepository $projectRepository,
        EntityManagerInterface $em,
        NotificationManager $notificationManager
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('accept_ai_recommendation', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid recommendation request.');
            return $this->redirectBack($request);
        }

        $index = (int) $request->request->get('recommendation_index', -1);
        $plan = $request->getSession()->get('ai_productivity_plan', []);
        $recommendations = $plan['recommendations'] ?? [];

        if (!is_array($recommendations) || !isset($recommendations[$index]) || !is_array($recommendations[$index])) {
            $this->addFlash('error', 'Recommendation not found. Generate a new AI plan.');
            return $this->redirectBack($request);
        }

        $recommendation = $recommendations[$index];
        $type = (string) ($recommendation['type'] ?? '');
        $targetId = (int) ($recommendation['target_id'] ?? 0);
        $setStatus = trim((string) ($recommendation['set_status'] ?? 'En cours'));

        if (!in_array($type, ['assignment', 'project'], true) || $targetId <= 0) {
            $this->addFlash('error', 'Invalid recommendation target.');
            return $this->redirectBack($request);
        }

        $user = $this->getUser();
        $targetTitle = (string) ($recommendation['title'] ?? 'Target');

        if ($type === 'assignment') {
            /** @var Assignment|null $assignment */
            $assignment = $assignmentRepository->find($targetId);
            if (!$assignment || $assignment->getUser()?->getId() !== $user->getId()) {
                $this->addFlash('error', 'Assignment no longer accessible.');
                return $this->redirectBack($request);
            }

            if (!$this->isDoneStatus($assignment->getStatut()) && $setStatus !== '') {
                $assignment->setStatut($setStatus);
            }
            $targetTitle = (string) $assignment->getTitre();
        } else {
            /** @var Project|null $project */
            $project = $projectRepository->find($targetId);
            if (!$project || $project->getUser()?->getId() !== $user->getId()) {
                $this->addFlash('error', 'Project no longer accessible.');
                return $this->redirectBack($request);
            }

            if (!$this->isDoneStatus($project->getStatut()) && $setStatus !== '') {
                $project->setStatut($setStatus);
            }
            $targetTitle = (string) $project->getTitre();
        }

        $challengeKey = sprintf('%s:%d', $type, $targetId);
        $challengeMinutes = max(10, (int) ($recommendation['challenge_minutes'] ?? 45));
        $rewardCoins = max(10, min(250, (int) ($recommendation['reward_coins'] ?? 50)));
        $acceptedAt = new \DateTimeImmutable();
        $expiresAt = $acceptedAt->modify(sprintf('+%d minutes', $challengeMinutes));

        $challenges = $request->getSession()->get('ai_productivity_challenges', []);
        if (!is_array($challenges)) {
            $challenges = [];
        }

        $challenges[$challengeKey] = [
            'key' => $challengeKey,
            'type' => $type,
            'target_id' => $targetId,
            'title' => $targetTitle,
            'challenge_minutes' => $challengeMinutes,
            'reward_coins' => $rewardCoins,
            'accepted_at' => $acceptedAt->format(\DateTimeInterface::ATOM),
            'expires_at' => $expiresAt->format(\DateTimeInterface::ATOM),
            'claimed' => false,
        ];

        $request->getSession()->set('ai_productivity_challenges', $challenges);
        $em->flush();

        $notificationManager->createNotification(
            $user,
            'AI challenge started',
            sprintf('Challenge accepted for "%s". Finish in %d minutes for %d coins.', $targetTitle, $challengeMinutes, $rewardCoins),
            'coins',
            null
        );

        $this->addFlash('success', sprintf('Challenge started: %s (%d min).', $targetTitle, $challengeMinutes));
        return $this->redirectBack($request);
    }

    #[Route('/claim', name: 'app_ai_productivity_claim', methods: ['POST'])]
    public function claim(
        Request $request,
        AssignmentRepository $assignmentRepository,
        ProjectRepository $projectRepository,
        EntityManagerInterface $em,
        NotificationManager $notificationManager
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('claim_ai_challenge', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid claim request.');
            return $this->redirectBack($request);
        }

        $type = (string) $request->request->get('type', '');
        $targetId = (int) $request->request->get('target_id', 0);
        $challengeKey = sprintf('%s:%d', $type, $targetId);
        $session = $request->getSession();
        $challenges = $session->get('ai_productivity_challenges', []);

        if (!is_array($challenges) || !isset($challenges[$challengeKey]) || !is_array($challenges[$challengeKey])) {
            $this->addFlash('error', 'Challenge not found.');
            return $this->redirectBack($request);
        }

        $challenge = $challenges[$challengeKey];
        if (($challenge['claimed'] ?? false) === true) {
            $this->addFlash('info', 'This challenge was already claimed.');
            return $this->redirectBack($request);
        }

        $user = $this->getUser();
        $completed = false;
        $statusValue = null;

        if ($type === 'assignment') {
            $assignment = $assignmentRepository->find($targetId);
            $completed = $assignment && $assignment->getUser()?->getId() === $user->getId() && $this->isDoneStatus($assignment->getStatut());
            $statusValue = $assignment?->getStatut();
        } elseif ($type === 'project') {
            $project = $projectRepository->find($targetId);
            $completed = $project && $project->getUser()?->getId() === $user->getId() && $this->isDoneStatus($project->getStatut());
            $statusValue = $project?->getStatut();
        }

        if (!$completed) {
            $this->addFlash('error', sprintf('Target is not completed yet (current status: %s).', (string) $statusValue));
            return $this->redirectBack($request);
        }

        $now = new \DateTimeImmutable();
        $expiresAt = new \DateTimeImmutable((string) ($challenge['expires_at'] ?? $now->format(\DateTimeInterface::ATOM)));
        if ($now > $expiresAt) {
            $this->addFlash('warning', 'Challenge expired before completion. No coin reward.');
            return $this->redirectBack($request);
        }

        $acceptedAt = (string) ($challenge['accepted_at'] ?? '');
        $reason = sprintf('ai_challenge:%s:%d:%s', $type, $targetId, $acceptedAt);

        $existingTx = $em->getRepository(CoinTransaction::class)->findOneBy([
            'user' => $user,
            'reason' => $reason,
        ]);
        if ($existingTx) {
            $this->addFlash('info', 'Challenge reward already granted.');
            return $this->redirectBack($request);
        }

        $coins = max(10, min(250, (int) ($challenge['reward_coins'] ?? 40)));
        $user->addCoins($coins);

        $transaction = new CoinTransaction();
        $transaction->setUser($user);
        $transaction->setAmount($coins);
        $transaction->setReason($reason);
        $em->persist($transaction);
        $em->flush();

        $challenges[$challengeKey]['claimed'] = true;
        $challenges[$challengeKey]['claimed_at'] = $now->format(\DateTimeInterface::ATOM);
        $session->set('ai_productivity_challenges', $challenges);

        $notificationManager->createNotification(
            $user,
            'AI challenge completed',
            sprintf('Great job! You earned +%d coins for finishing on time.', $coins),
            'coins',
            null
        );

        $this->addFlash('success', sprintf('Challenge completed: +%d coins.', $coins));
        return $this->redirectBack($request);
    }

    private function redirectBack(Request $request): RedirectResponse
    {
        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_dashboard'));
    }

    private function isDoneStatus(?string $status): bool
    {
        $normalized = mb_strtolower(trim((string) $status));
        $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);
        return in_array($normalized, ['termine', 'completed', 'done'], true);
    }
}

