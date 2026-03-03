<?php

namespace App\Pet\Behavior;

use App\Entity\Pet;

class LazyBehaviorStrategy implements PetBehaviorStrategyInterface
{
    public function profile(Pet $pet): array
    {
        return [
            'hungerTickFactor' => 0.85,
            'passiveHappinessDelta' => -1,
            'passiveEnergyDelta' => 1,
            'passiveHealthDelta' => 0,
            'actionXpMultiplier' => 0.9,
            'actionEnergyCostMultiplier' => 0.8,
        ];
    }

    public function moodMessages(Pet $pet): array
    {
        return [
            'Can we keep it simple today?',
            'I am conserving energy for later.',
            'Short tasks are perfect for me now.',
        ];
    }
}

