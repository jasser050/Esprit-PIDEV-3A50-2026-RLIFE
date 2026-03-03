<?php

namespace App\Service;

use App\Entity\Pet;
use App\Entity\User;
use App\Pet\Behavior\PetBehaviorFactory;
use Doctrine\ORM\EntityManagerInterface;

class PetProgressionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PetBehaviorFactory $behaviorFactory,
        private readonly PetAchievementService $achievementService,
        private readonly PetEventService $eventService
    ) {
    }

    /**
     * @return array{success:bool,action:string,level_up:bool,achievements:array<int,array<string,mixed>>,event:array<string,mixed>|null}
     */
    public function applyAction(User $user, Pet $pet, string $action): array
    {
        $action = mb_strtolower(trim($action));
        $strategy = $this->behaviorFactory->forPet($pet);
        $profile = $strategy->profile($pet);
        $energyCostMultiplier = (float) ($profile['actionEnergyCostMultiplier'] ?? 1.0);
        $xpMultiplier = (float) ($profile['actionXpMultiplier'] ?? 1.0);

        $levelUp = false;
        switch ($action) {
            case 'play':
                $pet->setEnergy($pet->getEnergy() - (int) round(16 * $energyCostMultiplier));
                $pet->setHappiness($pet->getHappiness() + 14);
                $pet->setHealth($pet->getHealth() + 2);
                $pet->setHunger($pet->getHunger() + 8);
                $pet->addXp((int) round(18 * $xpMultiplier));
                break;
            case 'sleep':
                $pet->setEnergy($pet->getEnergy() + 30);
                $pet->setHealth($pet->getHealth() + 8);
                $pet->setHappiness($pet->getHappiness() + 4);
                $pet->setHunger($pet->getHunger() + 6);
                $pet->addXp((int) round(10 * $xpMultiplier));
                break;
            case 'heal':
                if ($user->getCoins() < 80) {
                    return ['success' => false, 'action' => 'heal', 'level_up' => false, 'achievements' => [], 'event' => null];
                }
                $user->spendCoins(80);
                $pet->setHealth($pet->getHealth() + 30);
                $pet->setHappiness($pet->getHappiness() + 8);
                $pet->addCoinsSpent(80);
                $pet->addXp((int) round(14 * $xpMultiplier));
                break;
            case 'feed':
                if ($user->getCoins() < 50) {
                    return ['success' => false, 'action' => 'feed', 'level_up' => false, 'achievements' => [], 'event' => null];
                }
                $user->spendCoins(50);
                $pet->setHunger($pet->getHunger() - 25);
                $pet->setEnergy($pet->getEnergy() + 8);
                $pet->setHappiness($pet->getHappiness() + 6);
                $pet->addCoinsSpent(50);
                $pet->addXp((int) round(12 * $xpMultiplier));
                break;
            default:
                return ['success' => false, 'action' => $action, 'level_up' => false, 'achievements' => [], 'event' => null];
        }

        $pet->setLastInteractionAt(new \DateTimeImmutable());
        if ($pet->tryLevelUp()) {
            $levelUp = true;
        }

        $eventResult = $this->eventService->triggerRandomEvent($pet);
        if ($pet->tryLevelUp()) {
            $levelUp = true;
        }

        $achievements = $this->achievementService->evaluate($pet);
        foreach ($achievements as $achievement) {
            $user->addCoins((int) ($achievement['rewardCoins'] ?? 0));
            $pet->addXp((int) ($achievement['rewardXp'] ?? 0));
        }
        if ($pet->tryLevelUp()) {
            $levelUp = true;
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'action' => $action,
            'level_up' => $levelUp,
            'achievements' => $achievements,
            'event' => $eventResult['event'] ?? null,
        ];
    }
}

