<?php

namespace App\Controller;

use App\Entity\Pet;
use App\Entity\User;
use App\Repository\PetAchievementRepository;
use App\Repository\PetEventRepository;
use App\Repository\PetRepository;
use App\Repository\NotificationRepository;
use App\Repository\ProjectShareRepository;
use App\Repository\UserRepository;
use App\Service\PetAiService;
use App\Service\PetEventService;
use App\Service\NotificationManager;
use App\Service\PetHungerService;
use App\Service\PetProgressionService;
use App\Service\PetSocialService;
use App\Service\RewardService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pet')]
#[IsGranted('ROLE_USER')]
class PetController extends AbstractController
{
    public function __construct(
        private readonly RewardService $rewardService,
        private readonly NotificationManager $notificationManager,
        private readonly PetHungerService $petHungerService,
        private readonly PetProgressionService $petProgressionService,
        private readonly PetEventService $petEventService,
        private readonly PetAiService $petAiService,
        private readonly PetSocialService $petSocialService,
        private readonly PetEventRepository $petEventRepository,
        private readonly PetAchievementRepository $petAchievementRepository,
        private readonly PetRepository $petRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em
    ) {
    }

    #[Route('/choose', name: 'app_pet_choose', methods: ['GET', 'POST'])]
    public function choose(Request $request): Response
    {
        $user = $this->getUser();

        if ($user->getMainPet()) {
            $this->addFlash('info', 'You already have a companion.');
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $petType = (string) $request->request->get('petType');
            $petName = trim((string) $request->request->get('petName', ''));

            $validTypes = ['cat', 'dog', 'dragon', 'fox', 'bird', 'hamster', 'panda', 'rabbit'];

            if (!in_array($petType, $validTypes, true)) {
                $this->addFlash('error', 'Invalid companion type.');
            } elseif (mb_strlen($petName) < 2 || mb_strlen($petName) > 25) {
                $this->addFlash('error', 'Name must be between 2 and 25 characters.');
            } else {
                $pet = $this->rewardService->assignPet($user, $petType, $petName);
                $pet->setPersonality($this->suggestPersonalityFromType($petType));
                $pet->setRarity($this->randomRarity());
                $pet->setHappiness(72);
                $pet->setEnergy(80);
                $pet->setHealth(100);
                $pet->setXp(0);
                $this->em->flush();
                $this->addFlash('success', "{$petName} joined your journey.");
                return $this->redirectToRoute('app_dashboard');
            }
        }

        return $this->render('pet/choose.html.twig', [
            'petOptions' => [
                'cat' => 'Cat',
                'dog' => 'Dog',
                'dragon' => 'Dragon',
                'fox' => 'Fox',
                'bird' => 'Bird',
                'hamster' => 'Hamster',
                'panda' => 'Panda',
                'rabbit' => 'Rabbit',
            ],
        ]);
    }

    #[Route('/manage', name: 'app_pet_manage', methods: ['GET'])]
    public function manage(): Response
    {
        if (!$this->getUser()->getMainPet()) {
            return $this->redirectToRoute('app_pet_choose');
        }

        return $this->render('pet/manage.html.twig');
    }

    #[Route('/rename', name: 'app_pet_rename', methods: ['POST'])]
    public function rename(Request $request): Response
    {
        $newName = trim((string) ($request->request->get('name') ?? (json_decode($request->getContent(), true)['name'] ?? '')));

        if (mb_strlen($newName) < 2 || mb_strlen($newName) > 25) {
            return $this->errorResponse($request, 'Name must be between 2 and 25 characters.', 400);
        }

        $user = $this->getUser();
        $pet = $user->getMainPet();

        if (!$pet) {
            return $this->errorResponse($request, 'No companion found.', 404);
        }

        $pet->setName($newName);
        $this->em->flush();

        if ($this->isApiRequest($request)) {
            return $this->json([
                'success' => true,
                'name' => $newName,
                'message' => 'Name updated successfully.',
            ]);
        }

        $this->addFlash('success', 'Companion name updated.');
        return $this->redirectToRoute('app_pet_manage');
    }

    #[Route('/feed', name: 'app_pet_feed', methods: ['POST'])]
    public function feed(Request $request): Response
    {
        $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);
        $foodType = (string) ($data['foodType'] ?? $data['food_type'] ?? 'basic');

        $user = $this->getUser();
        $pet = $user->getMainPet();

        if (!$pet) {
            return $this->errorResponse($request, 'No companion found.', 404);
        }

        // Ensure hunger reflects elapsed time before applying food effect.
        $this->petHungerService->syncPetHunger($pet);

        $foods = [
            'basic' => ['cost' => 50, 'reduce' => 20, 'name' => 'Basic food'],
            'premium' => ['cost' => 120, 'reduce' => 45, 'name' => 'Premium meal'],
            'deluxe' => ['cost' => 200, 'reduce' => 75, 'name' => 'Deluxe feast'],
        ];

        if (!isset($foods[$foodType])) {
            return $this->errorResponse($request, 'Unknown food.', 400);
        }

        $food = $foods[$foodType];

        if ($user->getCoins() < $food['cost']) {
            return $this->errorResponse($request, "Not enough coins (need {$food['cost']}).", 400);
        }

        $user->spendCoins($food['cost']);

        $previousHunger = $pet->getHunger();
        $newHunger = max(0, $previousHunger - $food['reduce']);
        $pet->setHunger($newHunger);
        $pet->setLastHungerAt(new \DateTimeImmutable());
        $pet->addCoinsSpent($food['cost']);
        $pet->setHappiness($pet->getHappiness() + 5);
        $pet->setEnergy($pet->getEnergy() + 7);
        $pet->addXp(10);

        $levelUp = false;
        if ($previousHunger >= 85 && $newHunger <= 15) {
            $pet->setLevel($pet->getLevel() + 1);
            $levelUp = true;
        }
        if ($pet->tryLevelUp()) {
            $levelUp = true;
        }

        $this->em->flush();

        $this->notificationManager->createNotification(
            $user,
            'Coins spent',
            "-{$food['cost']} coins for {$food['name']}.",
            'coins',
            null
        );

        if ($this->isApiRequest($request)) {
            return $this->json([
                'success' => true,
                'message' => "Your companion ate {$food['name']}.",
                'newHunger' => $newHunger,
                'newLevel' => $pet->getLevel(),
                'xp' => $pet->getXp(),
                'happiness' => $pet->getHappiness(),
                'energy' => $pet->getEnergy(),
                'health' => $pet->getHealth(),
                'levelUp' => $levelUp,
                'coinsLeft' => $user->getCoins(),
                'coinsSpent' => $pet->getCoinsSpent(),
            ]);
        }

        $this->addFlash('success', "{$pet->getName()} has been fed.");
        return $this->redirectToRoute('app_pet_manage');
    }

    #[Route('/status', name: 'app_pet_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $pet = $this->getUser()->getMainPet();

        if (!$pet) {
            return $this->json(['hasPet' => false]);
        }

        $this->petHungerService->syncPetHunger($pet);
        $event = $this->petEventService->triggerRandomEvent($pet);
        $this->em->flush();
        $insight = $this->petAiService->buildPetInsight($this->getUser(), $pet);
        $recentEvents = array_map(static fn ($e) => [
            'type' => $e->getEventType(),
            'rarity' => $e->getRarity(),
            'title' => $e->getTitle(),
            'description' => $e->getDescription(),
            'at' => $e->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $this->petEventRepository->findRecentByPet($pet, 5));
        $achievements = array_map(static fn ($a) => [
            'code' => $a->getCode(),
            'title' => $a->getTitle(),
            'description' => $a->getDescription(),
            'rewardCoins' => $a->getRewardCoins(),
            'rewardXp' => $a->getRewardXp(),
            'unlockedAt' => $a->getUnlockedAt()->format(\DateTimeInterface::ATOM),
        ], $this->petAchievementRepository->findByPet($pet));

        return $this->json([
            'hasPet' => true,
            'name' => $pet->getName(),
            'type' => $pet->getType(),
            'level' => $pet->getLevel(),
            'rarity' => $pet->getRarity(),
            'evolution' => $this->resolvePetEvolution($pet),
            'personality' => $pet->getPersonality(),
            'xp' => $pet->getXp(),
            'xpToNextLevel' => $pet->getXpToNextLevel(),
            'hunger' => $pet->getHunger(),
            'happiness' => $pet->getHappiness(),
            'energy' => $pet->getEnergy(),
            'health' => $pet->getHealth(),
            'mood' => $pet->getMood(),
            'coinsSpent' => $pet->getCoinsSpent(),
            'coins' => $this->getUser()->getCoins(),
            'ai' => $insight,
            'eventTriggered' => (bool) ($event['triggered'] ?? false),
            'event' => $event['event'] ?? null,
            'recentEvents' => $recentEvents,
            'achievements' => $achievements,
        ]);
    }

    #[Route('/change-type', name: 'app_pet_change_type', methods: ['GET', 'POST'])]
    public function changeType(Request $request): Response
    {
        $user = $this->getUser();
        $currentPet = $user->getMainPet();

        if (!$currentPet) {
            $this->addFlash('warning', 'You do not have a companion yet.');
            return $this->redirectToRoute('app_pet_choose');
        }

        if ($request->isMethod('POST')) {
            $newType = (string) $request->request->get('petType');
            $validTypes = ['cat', 'dog', 'dragon', 'fox', 'bird', 'hamster', 'panda', 'rabbit'];

            if (!in_array($newType, $validTypes, true)) {
                $this->addFlash('error', 'Invalid companion type.');
            } elseif ($newType === $currentPet->getType()) {
                $this->addFlash('info', 'This is already your current companion type.');
            } else {
                $changeCost = 300;
                if ($user->getCoins() < $changeCost) {
                    $this->addFlash('error', "You need {$changeCost} coins to change companion type.");
                } else {
                    $user->spendCoins($changeCost);
                    $currentPet->setType($newType);
                    $this->em->flush();

                    $this->notificationManager->createNotification(
                        $user,
                        'Coins spent',
                        "-{$changeCost} coins for companion type change.",
                        'coins',
                        null
                    );

                    $this->addFlash('success', "Your companion is now a {$newType}.");
                    return $this->redirectToRoute('app_pet_manage');
                }
            }
        }

        return $this->render('pet/change-type.html.twig', [
            'currentPet' => $currentPet,
            'petOptions' => [
                'cat' => 'Cat',
                'dog' => 'Dog',
                'dragon' => 'Dragon',
                'fox' => 'Fox',
                'bird' => 'Bird',
                'hamster' => 'Hamster',
                'panda' => 'Panda',
                'rabbit' => 'Rabbit',
            ],
            'changeCost' => 300,
        ]);
    }

    #[Route('/metaverse', name: 'app_pet_metaverse', methods: ['GET'])]
    public function metaverse(ProjectShareRepository $projectShareRepository): Response
    {
        $user = $this->getUser();
        $connections = $projectShareRepository->findConnectionsForUser($user);

        $sharedProjectMap = [];
        foreach ($connections as $share) {
            $owner = $share->getSharedByUser();
            $guest = $share->getSharedWithUser();
            $project = $share->getProject();
            if (!$owner || !$guest || !$project) {
                continue;
            }

            $other = null;
            if ($owner->getId() === $user->getId()) {
                $other = $guest;
            } elseif ($guest->getId() === $user->getId()) {
                $other = $owner;
            }

            if (!$other) {
                continue;
            }

            $otherId = $other->getId();
            if (!isset($sharedProjectMap[$otherId])) {
                $sharedProjectMap[$otherId] = [];
            }

            $sharedProjectMap[$otherId][$project->getId()] = $project->getTitre();
        }

        $community = [];
        $currentPet = $user->getMainPet();
        if ($currentPet) {
            $community[] = [
                'user' => $user,
                'pet' => $currentPet,
                'evolution' => $this->resolvePetEvolution($currentPet),
                'is_self' => true,
                'shared_projects' => [],
            ];
        }

        foreach ($projectShareRepository->findConnectedUsers($user) as $connectedUser) {
            $pet = $connectedUser->getMainPet();
            if (!$pet) {
                continue;
            }

            $community[] = [
                'user' => $connectedUser,
                'pet' => $pet,
                'evolution' => $this->resolvePetEvolution($pet),
                'is_self' => false,
                'shared_projects' => array_values($sharedProjectMap[$connectedUser->getId()] ?? []),
            ];
        }

        return $this->render('pet/metaverse.html.twig', [
            'community' => $community,
        ]);
    }

    #[Route('/metaverse/reward', name: 'app_pet_metaverse_reward', methods: ['POST'])]
    public function metaverseReward(Request $request): JsonResponse
    {
        $payload = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);
        $requestedCoins = (int) ($payload['coins'] ?? 0);
        if ($requestedCoins <= 0) {
            return $this->json(['success' => false, 'error' => 'Invalid reward amount.'], 400);
        }

        $requestedCoins = min(120, max(1, $requestedCoins));
        $session = $request->getSession();
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $dateKey = 'pet.metaverse.reward_date';
        $totalKey = 'pet.metaverse.reward_total';
        $dailyLimit = 800;

        if ($session->get($dateKey) !== $today) {
            $session->set($dateKey, $today);
            $session->set($totalKey, 0);
        }

        $claimedToday = (int) $session->get($totalKey, 0);
        $remaining = max(0, $dailyLimit - $claimedToday);
        $grantedCoins = min($requestedCoins, $remaining);

        if ($grantedCoins <= 0) {
            return $this->json([
                'success' => false,
                'error' => 'Daily metaverse reward limit reached.',
                'dailyRemaining' => 0,
                'coins' => $this->getUser()->getCoins(),
            ], 429);
        }

        $user = $this->getUser();
        $user->addCoins($grantedCoins);
        $session->set($totalKey, $claimedToday + $grantedCoins);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'grantedCoins' => $grantedCoins,
            'coins' => $user->getCoins(),
            'dailyRemaining' => max(0, $dailyLimit - ((int) $session->get($totalKey, 0))),
        ]);
    }

    #[Route('/metaverse/communicate', name: 'app_pet_metaverse_communicate', methods: ['POST'])]
    public function metaverseCommunicate(Request $request): JsonResponse
    {
        $actor = $this->getUser();
        $actorPet = $actor->getMainPet();
        if (!$actorPet) {
            return $this->json(['success' => false, 'error' => 'No companion found.'], 404);
        }

        $payload = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);
        $targetUserId = (int) ($payload['targetUserId'] ?? 0);
        $targetUser = $targetUserId > 0 ? $this->userRepository->find($targetUserId) : null;
        if (!$targetUser instanceof User || $targetUser->getId() === $actor->getId()) {
            return $this->json(['success' => false, 'error' => 'Invalid target user.'], 400);
        }

        $targetPet = $targetUser->getMainPet();
        if (!$targetPet) {
            return $this->json(['success' => false, 'error' => 'Target user has no pet.'], 404);
        }

        $allowedTemplates = [
            'hello' => 'sends a friendly wave',
            'play' => 'invites your pet to play',
            'boost' => 'shares an energy boost',
            'team' => 'proposes a teamwork sync',
            'guard' => 'offers protection mode',
        ];
        $template = (string) ($payload['template'] ?? 'hello');
        if (!isset($allowedTemplates[$template])) {
            $template = 'hello';
        }

        $title = 'Pet Communication';
        $message = sprintf(
            '%s (%s) %s to %s.',
            $actorPet->getName(),
            ucfirst($actorPet->getType()),
            $allowedTemplates[$template],
            $targetPet->getName()
        );

        $this->notificationManager->createNotification(
            $targetUser,
            $title,
            $message,
            'pet_metaverse_msg',
            $this->generateUrl('app_pet_metaverse')
        );

        return $this->json([
            'success' => true,
            'message' => 'Communication sent.',
        ]);
    }

    #[Route('/metaverse/communications', name: 'app_pet_metaverse_communications', methods: ['GET'])]
    public function metaverseCommunications(Request $request): JsonResponse
    {
        $sinceId = max(0, (int) $request->query->get('sinceId', 0));
        $qb = $this->notificationRepository->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.type = :type')
            ->setParameter('user', $this->getUser())
            ->setParameter('type', 'pet_metaverse_msg')
            ->setMaxResults(20);

        if ($sinceId > 0) {
            $qb->andWhere('n.id > :sinceId')
                ->setParameter('sinceId', $sinceId)
                ->orderBy('n.id', 'ASC');
        } else {
            // Bootstrap from the most recent communications to avoid replaying very old history.
            $qb->orderBy('n.id', 'DESC');
        }

        $notifications = $qb->getQuery()->getResult();
        if ($sinceId === 0) {
            $notifications = array_reverse($notifications);
        }

        $rows = array_map(static fn ($n) => [
            'id' => $n->getId(),
            'title' => $n->getTitle(),
            'message' => $n->getMessage(),
            'createdAt' => $n->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], $notifications);

        return $this->json([
            'success' => true,
            'messages' => $rows,
            'lastId' => !empty($rows) ? (int) end($rows)['id'] : $sinceId,
        ]);
    }

    #[Route('/action', name: 'app_pet_action', methods: ['POST'])]
    public function action(Request $request): JsonResponse
    {
        $pet = $this->getUser()->getMainPet();
        if (!$pet) {
            return $this->json(['success' => false, 'error' => 'No companion found.'], 404);
        }

        $payload = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);
        $action = mb_strtolower(trim((string) ($payload['action'] ?? '')));
        if (!in_array($action, ['play', 'sleep', 'heal', 'feed'], true)) {
            return $this->json(['success' => false, 'error' => 'Unknown action.'], 400);
        }

        $this->petHungerService->syncPetHunger($pet);
        $result = $this->petProgressionService->applyAction($this->getUser(), $pet, $action);
        if (!$result['success']) {
            return $this->json(['success' => false, 'error' => 'Action failed.'], 400);
        }

        $insight = $this->petAiService->buildPetInsight($this->getUser(), $pet);

        return $this->json([
            'success' => true,
            'action' => $action,
            'levelUp' => (bool) ($result['level_up'] ?? false),
            'achievements' => $result['achievements'] ?? [],
            'event' => $result['event'] ?? null,
            'pet' => [
                'level' => $pet->getLevel(),
                'xp' => $pet->getXp(),
                'xpToNextLevel' => $pet->getXpToNextLevel(),
                'hunger' => $pet->getHunger(),
                'happiness' => $pet->getHappiness(),
                'energy' => $pet->getEnergy(),
                'health' => $pet->getHealth(),
                'mood' => $pet->getMood(),
                'rarity' => $pet->getRarity(),
                'evolution' => $this->resolvePetEvolution($pet),
                'personality' => $pet->getPersonality(),
            ],
            'coins' => $this->getUser()->getCoins(),
            'ai' => $insight,
        ]);
    }

    #[Route('/leaderboard', name: 'app_pet_leaderboard', methods: ['GET'])]
    public function leaderboard(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'leaderboard' => $this->petSocialService->leaderboard(20),
        ]);
    }

    #[Route('/opponents', name: 'app_pet_opponents', methods: ['GET'])]
    public function opponents(): JsonResponse
    {
        $user = $this->getUser();
        $pets = $this->petSocialService->socialOpponents($user);
        $rows = array_map(fn (Pet $pet) => [
            'petId' => $pet->getId(),
            'petName' => $pet->getName(),
            'petType' => $pet->getType(),
            'petLevel' => $pet->getLevel(),
            'petRarity' => $pet->getRarity(),
            'ownerId' => $pet->getUser()?->getId(),
            'ownerName' => $pet->getUser()?->getFullName() ?: $pet->getUser()?->getEmail(),
        ], $pets);

        return $this->json(['success' => true, 'opponents' => $rows]);
    }

    #[Route('/battle/{opponentUserId}', name: 'app_pet_battle', methods: ['POST'])]
    public function battle(int $opponentUserId): JsonResponse
    {
        $user = $this->getUser();
        $myPet = $user->getMainPet();
        if (!$myPet) {
            return $this->json(['success' => false, 'error' => 'No companion found.'], 404);
        }

        $opponentUser = $this->userRepository->find($opponentUserId);
        if (!$opponentUser instanceof User) {
            return $this->json(['success' => false, 'error' => 'Opponent not found.'], 404);
        }
        $opponentPet = $opponentUser->getMainPet();
        if (!$opponentPet) {
            return $this->json(['success' => false, 'error' => 'Opponent has no pet.'], 404);
        }

        $result = $this->petSocialService->battle($user, $myPet, $opponentPet);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'battle' => $result,
            'myPet' => [
                'level' => $myPet->getLevel(),
                'xp' => $myPet->getXp(),
                'happiness' => $myPet->getHappiness(),
                'energy' => $myPet->getEnergy(),
                'health' => $myPet->getHealth(),
            ],
        ]);
    }

    #[Route('/shop/catalog', name: 'app_pet_shop_catalog', methods: ['GET'])]
    public function shopCatalog(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'items' => [
                ['id' => 'snack_basic', 'name' => 'Basic Snack', 'type' => 'food', 'cost' => 45, 'effects' => ['hunger' => -18, 'happiness' => 4]],
                ['id' => 'snack_premium', 'name' => 'Premium Snack', 'type' => 'food', 'cost' => 120, 'effects' => ['hunger' => -40, 'happiness' => 8, 'energy' => 5]],
                ['id' => 'toy_neon', 'name' => 'Neon Toy', 'type' => 'toy', 'cost' => 180, 'effects' => ['happiness' => 15, 'xp' => 12]],
                ['id' => 'potion_heal', 'name' => 'Heal Potion', 'type' => 'heal', 'cost' => 160, 'effects' => ['health' => 28]],
                ['id' => 'legendary_orb', 'name' => 'Legendary Orb', 'type' => 'boost', 'cost' => 420, 'effects' => ['xp' => 55, 'energy' => 15, 'happiness' => 12]],
            ],
        ]);
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('content-type'), 'application/json');
    }

    private function errorResponse(Request $request, string $message, int $statusCode): Response
    {
        if ($this->isApiRequest($request)) {
            return $this->json(['success' => false, 'error' => $message], $statusCode);
        }

        $this->addFlash('error', $message);
        return new RedirectResponse($request->headers->get('referer') ?: $this->generateUrl('app_dashboard'));
    }

    private function suggestPersonalityFromType(string $type): string
    {
        return match (mb_strtolower(trim($type))) {
            'dog', 'fox', 'bird' => Pet::PERSONALITY_PLAYFUL,
            'dragon' => Pet::PERSONALITY_AGGRESSIVE,
            'hamster', 'panda', 'rabbit' => Pet::PERSONALITY_LAZY,
            default => Pet::PERSONALITY_CALM,
        };
    }

    private function randomRarity(): string
    {
        $roll = random_int(1, 100);
        if ($roll >= 98) return Pet::RARITY_LEGENDARY;
        if ($roll >= 90) return Pet::RARITY_EPIC;
        if ($roll >= 70) return Pet::RARITY_RARE;
        return Pet::RARITY_COMMON;
    }

    /**
     * @return array{code:string,label:string,index:int,level:int,rarity:string,rarityRank:int}
     */
    private function resolvePetEvolution(Pet $pet): array
    {
        $rarity = mb_strtolower(trim($pet->getRarity()));
        $rarityRank = match ($rarity) {
            Pet::RARITY_LEGENDARY => 3,
            Pet::RARITY_EPIC => 2,
            Pet::RARITY_RARE => 1,
            default => 0,
        };

        $level = max(1, $pet->getLevel());
        if ($level >= 18 || $rarityRank >= 3) {
            return ['code' => 'mythic', 'label' => 'Mythic Form', 'index' => 3, 'level' => $level, 'rarity' => $rarity, 'rarityRank' => $rarityRank];
        }
        if ($level >= 12 || $rarityRank >= 2) {
            return ['code' => 'ascended', 'label' => 'Ascended Form', 'index' => 2, 'level' => $level, 'rarity' => $rarity, 'rarityRank' => $rarityRank];
        }
        if ($level >= 6 || $rarityRank >= 1) {
            return ['code' => 'evolved', 'label' => 'Evolved Form', 'index' => 1, 'level' => $level, 'rarity' => $rarity, 'rarityRank' => $rarityRank];
        }

        return ['code' => 'base', 'label' => 'Base Form', 'index' => 0, 'level' => $level, 'rarity' => $rarity, 'rarityRank' => $rarityRank];
    }
}
