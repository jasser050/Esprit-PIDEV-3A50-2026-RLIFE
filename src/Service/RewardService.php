<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\CoinTransaction;
use App\Entity\Pet;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RewardService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationManager $notificationManager
    ) {
    }

    public function assignPet(User $user, string $petType, string $petName): Pet
    {
        if ($user->getMainPet()) {
            throw new \Exception('This user already has a companion.');
        }

        $pet = new Pet();
        $pet->setType($petType);
        $pet->setName($petName);
        $pet->setLevel(1);
        $pet->setHunger(45);
        $pet->setUser($user);

        $this->em->persist($pet);
        $this->em->flush();

        return $pet;
    }

    public function assignInitialPet(User $user): ?Pet
    {
        if ($user->getMainPet()) {
            return null;
        }

        $types = ['cat', 'dog', 'bird', 'hamster', 'dragon'];
        $names = ['Pico', 'Buddy', 'Luna', 'Milo', 'Nala'];

        $type = $types[array_rand($types)];
        $name = $names[array_rand($names)];

        return $this->assignPet($user, $type, $name);
    }

    public function awardCoinsForAssignment(User $user, Assignment $assignment): void
    {
        $status = $this->normalizeStatus($assignment->getStatut());
        if (!in_array($status, ['termine', 'completed', 'done'], true)) {
            return;
        }

        if (!$assignment->getDateFin()) {
            return;
        }

        $reasonKey = sprintf('assignment:%d:completion_bonus', $assignment->getId());
        $alreadyRewarded = $this->em->getRepository(CoinTransaction::class)->findOneBy([
            'user' => $user,
            'reason' => $reasonKey,
        ]);

        if ($alreadyRewarded) {
            return;
        }

        $coins = $this->computeAssignmentCoins($assignment);

        $user->addCoins($coins);

        $transaction = new CoinTransaction();
        $transaction->setUser($user);
        $transaction->setAmount($coins);
        $transaction->setReason($reasonKey);

        $this->em->persist($transaction);
        $this->em->flush();

        $this->notificationManager->createNotification(
            $user,
            'Coins earned',
            "+{$coins} coins for completing \"{$assignment->getTitre()}\".",
            'coins',
            null
        );
    }

    public function feedPet(User $user, int $foodCost = 100): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'newHunger' => null,
            'levelUp' => false,
            'coinsLeft' => $user->getCoins(),
        ];

        if ($user->getCoins() < $foodCost) {
            $result['message'] = "Not enough coins (need {$foodCost}, you have {$user->getCoins()})";
            return $result;
        }

        $user->spendCoins($foodCost);

        $pet = $user->getMainPet();
        if (!$pet) {
            $result['message'] = 'No companion found for this user';
            $this->em->flush();
            return $result;
        }

        $oldHunger = $pet->getHunger();
        $newHunger = max(0, $oldHunger - 60);
        $pet->setHunger($newHunger);
        $pet->addCoinsSpent($foodCost);

        $levelUp = false;
        if ($newHunger <= 10 && $oldHunger >= 75) {
            $pet->setLevel($pet->getLevel() + 1);
            $levelUp = true;
            $result['message'] = 'Your companion leveled up after feeding.';
        } else {
            $result['message'] = "Your companion has been fed. Hunger is now {$newHunger}/100";
        }

        $transaction = new CoinTransaction();
        $transaction->setUser($user);
        $transaction->setAmount(-$foodCost);
        $transaction->setReason("food:pet:{$pet->getId()}:{$foodCost}");
        $this->em->persist($transaction);

        $this->em->flush();

        $this->notificationManager->createNotification(
            $user,
            'Coins spent',
            "-{$foodCost} coins to feed {$pet->getName()}.",
            'coins',
            null
        );

        $result['success'] = true;
        $result['newHunger'] = $newHunger;
        $result['levelUp'] = $levelUp;
        $result['coinsLeft'] = $user->getCoins();

        return $result;
    }

    private function computeAssignmentCoins(Assignment $assignment): int
    {
        $base = 20;

        $priority = mb_strtolower((string) $assignment->getPriorite());
        $priorityBonus = match ($priority) {
            'haute', 'high' => 20,
            'moyenne', 'medium' => 12,
            'basse', 'low' => 6,
            default => 8,
        };

        $durationBonus = 0;
        if ($assignment->getDateDebut() && $assignment->getDateFin()) {
            $durationDays = max(1, (int) $assignment->getDateDebut()->diff($assignment->getDateFin())->days);
            $durationBonus = min(15, (int) floor($durationDays / 2));
        }

        $earlyBonus = 0;
        $today = new \DateTimeImmutable('today');
        $dueDate = \DateTimeImmutable::createFromInterface($assignment->getDateFin());
        if ($dueDate > $today) {
            $daysEarly = $today->diff($dueDate)->days;
            $earlyBonus = min(30, $daysEarly * 3);
        }

        return max(10, $base + $priorityBonus + $durationBonus + $earlyBonus);
    }

    private function normalizeStatus(?string $status): string
    {
        $status = mb_strtolower(trim((string) $status));
        $status = str_replace(['é', 'è', 'ê'], 'e', $status);
        return $status;
    }
}
