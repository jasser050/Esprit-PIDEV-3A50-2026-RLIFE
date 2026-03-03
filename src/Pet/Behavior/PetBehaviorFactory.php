<?php

namespace App\Pet\Behavior;

use App\Entity\Pet;

class PetBehaviorFactory
{
    public function __construct(
        private readonly PlayfulBehaviorStrategy $playful,
        private readonly LazyBehaviorStrategy $lazy,
        private readonly AggressiveBehaviorStrategy $aggressive,
        private readonly CalmBehaviorStrategy $calm
    ) {
    }

    public function forPet(Pet $pet): PetBehaviorStrategyInterface
    {
        return match ($pet->getPersonality()) {
            Pet::PERSONALITY_PLAYFUL => $this->playful,
            Pet::PERSONALITY_LAZY => $this->lazy,
            Pet::PERSONALITY_AGGRESSIVE => $this->aggressive,
            default => $this->calm,
        };
    }
}

