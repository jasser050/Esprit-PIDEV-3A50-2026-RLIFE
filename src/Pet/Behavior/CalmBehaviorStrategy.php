<?php

namespace App\Pet\Behavior;

use App\Entity\Pet;

class CalmBehaviorStrategy implements PetBehaviorStrategyInterface
{
    public function profile(Pet $pet): array
    {
        return [
            'hungerTickFactor' => 1.0,
            'passiveHappinessDelta' => -1,
            'passiveEnergyDelta' => -1,
            'passiveHealthDelta' => 0,
            'actionXpMultiplier' => 1.0,
            'actionEnergyCostMultiplier' => 1.0,
        ];
    }

    public function moodMessages(Pet $pet): array
    {
        return [
            'I am staying balanced and focused.',
            'Steady progress is still progress.',
            'Calm mode active. Ready for the next task.',
        ];
    }
}

