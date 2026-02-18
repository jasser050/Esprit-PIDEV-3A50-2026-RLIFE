<?php

namespace App\Controller;

use App\Service\NotificationManager;
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

            $validTypes = ['cat', 'dog', 'dragon', 'fox', 'bird', 'hamster'];

            if (!in_array($petType, $validTypes, true)) {
                $this->addFlash('error', 'Invalid companion type.');
            } elseif (mb_strlen($petName) < 2 || mb_strlen($petName) > 25) {
                $this->addFlash('error', 'Name must be between 2 and 25 characters.');
            } else {
                $this->rewardService->assignPet($user, $petType, $petName);
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
        $pet->addCoinsSpent($food['cost']);

        $levelUp = false;
        if ($previousHunger >= 85 && $newHunger <= 15) {
            $pet->setLevel($pet->getLevel() + 1);
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

        return $this->json([
            'hasPet' => true,
            'name' => $pet->getName(),
            'type' => $pet->getType(),
            'level' => $pet->getLevel(),
            'hunger' => $pet->getHunger(),
            'mood' => $pet->getMood(),
            'coinsSpent' => $pet->getCoinsSpent(),
            'coins' => $this->getUser()->getCoins(),
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
            $validTypes = ['cat', 'dog', 'dragon', 'fox', 'bird', 'hamster'];

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
            ],
            'changeCost' => 300,
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
}
