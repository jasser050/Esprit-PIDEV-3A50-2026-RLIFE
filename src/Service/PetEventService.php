<?php

namespace App\Service;

use App\Entity\Pet;
use App\Entity\PetEvent;
use App\Repository\PetEventRepository;
use Doctrine\ORM\EntityManagerInterface;

class PetEventService
{
    public function __construct(
        private readonly PetEventRepository $eventRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array{triggered:bool,event?:array<string,mixed>}
     */
    public function triggerRandomEvent(Pet $pet, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $lastEventAt = $pet->getLastEventAt();
        if ($lastEventAt && ($now->getTimestamp() - $lastEventAt->getTimestamp()) < 1800) {
            return ['triggered' => false];
        }

        $roll = random_int(1, 100);
        if ($roll > 25) {
            return ['triggered' => false];
        }

        $pool = [
            [
                'type' => 'treasure',
                'rarity' => Pet::RARITY_RARE,
                'title' => 'Hidden Treasure',
                'description' => 'Your pet found a hidden treasure chest.',
                'effects' => ['xp' => 25, 'happiness' => 12, 'health' => 4],
            ],
            [
                'type' => 'sickness',
                'rarity' => Pet::RARITY_COMMON,
                'title' => 'Minor Sickness',
                'description' => 'Your pet feels weak and needs care.',
                'effects' => ['health' => -15, 'energy' => -10, 'happiness' => -8],
            ],
            [
                'type' => 'motivation',
                'rarity' => Pet::RARITY_COMMON,
                'title' => 'Motivation Wave',
                'description' => 'Your recent progress inspired your pet.',
                'effects' => ['xp' => 10, 'happiness' => 8, 'energy' => 7],
            ],
            [
                'type' => 'bonus',
                'rarity' => Pet::RARITY_EPIC,
                'title' => 'Epic Bonus',
                'description' => 'A rare cosmic bonus boosts your pet stats.',
                'effects' => ['xp' => 45, 'happiness' => 18, 'energy' => 14, 'health' => 10],
            ],
        ];

        $selected = $pool[array_rand($pool)];
        $this->applyEffects($pet, $selected['effects']);
        $pet->setLastEventAt($now);

        $event = new PetEvent();
        $event->setPet($pet);
        $event->setEventType((string) $selected['type']);
        $event->setRarity((string) $selected['rarity']);
        $event->setTitle((string) $selected['title']);
        $event->setDescription((string) $selected['description']);
        $event->setEffects((array) $selected['effects']);
        $this->entityManager->persist($event);

        return [
            'triggered' => true,
            'event' => [
                'type' => $event->getEventType(),
                'rarity' => $event->getRarity(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'effects' => $event->getEffects(),
            ],
        ];
    }

    /**
     * @return PetEvent[]
     */
    public function recentEvents(Pet $pet, int $limit = 10): array
    {
        return $this->eventRepository->findRecentByPet($pet, $limit);
    }

    private function applyEffects(Pet $pet, array $effects): void
    {
        if (isset($effects['xp'])) {
            $pet->addXp((int) $effects['xp']);
            $pet->tryLevelUp();
        }
        if (isset($effects['happiness'])) {
            $pet->setHappiness($pet->getHappiness() + (int) $effects['happiness']);
        }
        if (isset($effects['energy'])) {
            $pet->setEnergy($pet->getEnergy() + (int) $effects['energy']);
        }
        if (isset($effects['health'])) {
            $pet->setHealth($pet->getHealth() + (int) $effects['health']);
        }
    }
}

