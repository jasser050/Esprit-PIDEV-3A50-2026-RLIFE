<?php

namespace App\Service;

use App\Entity\Pet;
use App\Entity\PetAchievement;
use App\Repository\PetAchievementRepository;
use Doctrine\ORM\EntityManagerInterface;

class PetAchievementService
{
    public function __construct(
        private readonly PetAchievementRepository $achievementRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<int,array{code:string,title:string,rewardCoins:int,rewardXp:int}>
     */
    public function evaluate(Pet $pet): array
    {
        $unlocked = [];
        $rules = [
            ['code' => 'pet_level_5', 'ok' => $pet->getLevel() >= 5, 'title' => 'Rising Companion', 'description' => 'Reach level 5.', 'coins' => 150, 'xp' => 40],
            ['code' => 'pet_level_10', 'ok' => $pet->getLevel() >= 10, 'title' => 'Elite Companion', 'description' => 'Reach level 10.', 'coins' => 320, 'xp' => 80],
            ['code' => 'pet_happiness_90', 'ok' => $pet->getHappiness() >= 90, 'title' => 'Joy Master', 'description' => 'Reach 90 happiness.', 'coins' => 120, 'xp' => 30],
            ['code' => 'pet_health_100', 'ok' => $pet->getHealth() >= 100, 'title' => 'Perfect Health', 'description' => 'Reach full health.', 'coins' => 100, 'xp' => 20],
            ['code' => 'pet_coin_spent_1000', 'ok' => $pet->getCoinsSpent() >= 1000, 'title' => 'Dedicated Caretaker', 'description' => 'Spend 1000 coins on your pet.', 'coins' => 250, 'xp' => 50],
        ];

        foreach ($rules as $rule) {
            if (!$rule['ok']) {
                continue;
            }
            if ($this->achievementRepository->hasCode($pet, $rule['code'])) {
                continue;
            }

            $achievement = new PetAchievement();
            $achievement->setPet($pet);
            $achievement->setCode($rule['code']);
            $achievement->setTitle($rule['title']);
            $achievement->setDescription($rule['description']);
            $achievement->setRewardCoins($rule['coins']);
            $achievement->setRewardXp($rule['xp']);
            $this->entityManager->persist($achievement);

            $unlocked[] = [
                'code' => $rule['code'],
                'title' => $rule['title'],
                'rewardCoins' => $rule['coins'],
                'rewardXp' => $rule['xp'],
            ];
        }

        return $unlocked;
    }
}

