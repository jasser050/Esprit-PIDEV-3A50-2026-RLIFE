<?php

namespace App\Service;

use App\Entity\Pet;
use App\Entity\User;
use App\Repository\PetRepository;
use App\Repository\ProjectShareRepository;

class PetSocialService
{
    public function __construct(
        private readonly ProjectShareRepository $shareRepository,
        private readonly PetRepository $petRepository
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function leaderboard(int $limit = 20): array
    {
        $pets = $this->petRepository->findTopPetsByPower($limit);
        $rows = [];
        foreach ($pets as $pet) {
            if (!$pet instanceof Pet) {
                continue;
            }
            $rows[] = [
                'pet_id' => $pet->getId(),
                'pet_name' => $pet->getName(),
                'pet_type' => $pet->getType(),
                'user' => $pet->getUser()?->getFullName() ?: $pet->getUser()?->getEmail(),
                'level' => $pet->getLevel(),
                'xp' => $pet->getXp(),
                'happiness' => $pet->getHappiness(),
                'health' => $pet->getHealth(),
                'rarity' => $pet->getRarity(),
                'power' => $this->powerScore($pet),
            ];
        }

        return $rows;
    }

    /**
     * @return array{winner:string,log:string,my_score:int,opponent_score:int}
     */
    public function battle(User $user, Pet $myPet, Pet $opponent): array
    {
        $myScore = $this->powerScore($myPet) + random_int(0, 18);
        $opponentScore = $this->powerScore($opponent) + random_int(0, 18);
        $winner = $myScore >= $opponentScore ? 'me' : 'opponent';

        if ($winner === 'me') {
            $myPet->addXp(20);
            $myPet->setHappiness($myPet->getHappiness() + 8);
        } else {
            $myPet->setHappiness($myPet->getHappiness() - 4);
            $myPet->setEnergy($myPet->getEnergy() - 6);
        }
        $myPet->tryLevelUp();

        return [
            'winner' => $winner,
            'log' => sprintf('%s battled %s. Result: %s.', $myPet->getName(), $opponent->getName(), $winner === 'me' ? 'Victory' : 'Defeat'),
            'my_score' => $myScore,
            'opponent_score' => $opponentScore,
        ];
    }

    /**
     * @return Pet[]
     */
    public function socialOpponents(User $user): array
    {
        $connected = $this->shareRepository->findConnectedUsers($user);
        $pets = [];
        foreach ($connected as $connectedUser) {
            $pet = $connectedUser->getMainPet();
            if ($pet) {
                $pets[] = $pet;
            }
        }

        return $pets;
    }

    public function powerScore(Pet $pet): int
    {
        return (int) round(
            ($pet->getLevel() * 12)
            + ($pet->getXp() / 12)
            + ($pet->getHappiness() * 0.5)
            + ($pet->getHealth() * 0.7)
            + ($pet->getEnergy() * 0.4)
        );
    }
}

