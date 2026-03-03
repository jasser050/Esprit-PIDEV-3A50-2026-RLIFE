<?php

namespace App\Service;

use App\Entity\Pet;
use App\Pet\Behavior\PetBehaviorFactory;
use App\Repository\PetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PetHungerService
{
    private const HUNGER_TICK_SECONDS = 1800; // every 30 minutes

    public function __construct(
        private readonly PetRepository $petRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PetBehaviorFactory $behaviorFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function syncPetHunger(Pet $pet, ?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();
        $last = $pet->getLastHungerAt();
        $elapsed = $now->getTimestamp() - $last->getTimestamp();

        if ($elapsed < self::HUNGER_TICK_SECONDS) {
            return false;
        }

        $ticks = intdiv($elapsed, self::HUNGER_TICK_SECONDS);
        $ratePerTick = 1 + intdiv(max(1, $pet->getLevel()) - 1, 10);
        $behavior = $this->behaviorFactory->forPet($pet)->profile($pet);
        $hungerTickFactor = max(0.5, (float) ($behavior['hungerTickFactor'] ?? 1.0));
        $increase = (int) round($ticks * $ratePerTick * $hungerTickFactor);

        $oldHunger = $pet->getHunger();
        $newHunger = min(100, $oldHunger + $increase);

        $pet->setHunger($newHunger);
        $pet->setHappiness($pet->getHappiness() + ((int) ($behavior['passiveHappinessDelta'] ?? -1) * $ticks));
        $pet->setEnergy($pet->getEnergy() + ((int) ($behavior['passiveEnergyDelta'] ?? -1) * $ticks));
        $pet->setHealth($pet->getHealth() + ((int) ($behavior['passiveHealthDelta'] ?? 0) * $ticks));
        if ($newHunger >= 90) {
            $pet->setHealth($pet->getHealth() - 1);
            $pet->setHappiness($pet->getHappiness() - 2);
        }
        $pet->setLastHungerAt(
            $last->modify(sprintf('+%d seconds', $ticks * self::HUNGER_TICK_SECONDS))
        );

        if ($newHunger !== $oldHunger) {
            $this->logger->debug(sprintf(
                'Pet #%d hunger updated: %d -> %d (%d ticks).',
                (int) $pet->getId(),
                $oldHunger,
                $newHunger,
                $ticks
            ));
        }

        return true;
    }

    public function increaseHungerForAll(): int
    {
        return $this->syncHungerForAll();
    }

    public function syncHungerForAll(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();
        $pets = $this->petRepository->findAll();

        if (empty($pets)) {
            $this->logger->info('No pets found for hunger sync.');
            return 0;
        }

        $updatedCount = 0;
        foreach ($pets as $pet) {
            if ($this->syncPetHunger($pet, $now)) {
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $this->logger->info(sprintf('%d pet(s) hunger synced.', $updatedCount));
        }

        return $updatedCount;
    }
}

