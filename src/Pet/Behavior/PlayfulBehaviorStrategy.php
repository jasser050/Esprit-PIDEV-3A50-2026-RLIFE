<?php

namespace App\Pet\Behavior;

use App\Entity\Pet;

class PlayfulBehaviorStrategy implements PetBehaviorStrategyInterface
{
    public function profile(Pet $pet): array
    {
        return [
            'hungerTickFactor' => 1.2,
            'passiveHappinessDelta' => -2,
            'passiveEnergyDelta' => -2,
            'passiveHealthDelta' => 0,
            'actionXpMultiplier' => 1.15,
            'actionEnergyCostMultiplier' => 1.1,
        ];
    }

    public function moodMessages(Pet $pet): array
    {
        return [
            'Let us play and gain XP quickly.',
            'I am full of ideas and random energy.',
            'Adventure mode on. Give me a mission.',
        ];
    }
}

