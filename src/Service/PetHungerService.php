<?php

namespace App\Service;

use App\Entity\Pet;
use App\Repository\PetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PetHungerService
{
    private const HUNGER_TICK_SECONDS = 1800; // every 30 minutes

    public function __construct(
        private readonly PetRepository $petRepository,
        private readonly EntityManagerInterface $entityManager,
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
        $increase = $ticks * $ratePerTick;

        $oldHunger = $pet->getHunger();
        $newHunger = min(100, $oldHunger + $increase);

        $pet->setHunger($newHunger);
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

