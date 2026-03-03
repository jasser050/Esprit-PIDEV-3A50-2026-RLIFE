<?php

namespace App\Pet\Behavior;

use App\Entity\Pet;

class AggressiveBehaviorStrategy implements PetBehaviorStrategyInterface
{
    public function profile(Pet $pet): array
    {
        return [
            'hungerTickFactor' => 1.35,
            'passiveHappinessDelta' => -2,
            'passiveEnergyDelta' => -2,
            'passiveHealthDelta' => -1,
            'actionXpMultiplier' => 1.2,
            'actionEnergyCostMultiplier' => 1.2,
        ];
    }

    public function moodMessages(Pet $pet): array
    {
        return [
            'I want tougher challenges now.',
            'Push harder. We can dominate this.',
            'Battle-ready. Point me to the target.',
        ];
    }
}

