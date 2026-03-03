<?php

namespace App\Pet\Behavior;

use App\Entity\Pet;

interface PetBehaviorStrategyInterface
{
    /**
     * @return array{
     *   hungerTickFactor:float,
     *   passiveHappinessDelta:int,
     *   passiveEnergyDelta:int,
     *   passiveHealthDelta:int,
     *   actionXpMultiplier:float,
     *   actionEnergyCostMultiplier:float
     * }
     */
    public function profile(Pet $pet): array;

    /**
     * @return list<string>
     */
    public function moodMessages(Pet $pet): array;
}

