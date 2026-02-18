<?php

namespace App\Service;

use App\Entity\Pet;
use App\Repository\PetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PetHungerService
{
    public function __construct(
        private readonly PetRepository $petRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Augmente la faim de tous les pets de façon progressive et réaliste
     * 
     * - Plus le pet est de haut niveau → il a plus faim (besoins énergétiques plus élevés)
     * - Petite variation aléatoire pour éviter que tous les pets aient exactement la même faim
     * - Limite à 100
     * - Log les mises à jour importantes
     */
    public function increaseHungerForAll(): int
    {
        $pets = $this->petRepository->findAll();
        
        if (empty($pets)) {
            $this->logger->info('Aucun pet trouvé pour mise à jour de la faim.');
            return 0;
        }

        $updatedCount = 0;

        foreach ($pets as $pet) {
            // Base : 5 à 12 points selon le niveau
            $baseIncrease = 5 + min($pet->getLevel(), 10); // max +15 pour niveaux élevés
            
            // Variation aléatoire ±30% pour plus de réalisme
            $variation = random_int(-3, 3);
            $increase = $baseIncrease + $variation;

            $currentHunger = $pet->getHunger();
            $newHunger = min(100, $currentHunger + $increase);

            if ($newHunger > $currentHunger) {
                $pet->setHunger($newHunger);
                $updatedCount++;

                // Log seulement si la faim augmente significativement ou atteint max
                if ($increase >= 10 || $newHunger >= 95) {
                    $this->logger->info(
                        "Faim augmentée pour pet #{$pet->getId()} ({$pet->getName()}) : " .
                        "{$currentHunger} → {$newHunger} (+{$increase}) - niveau {$pet->getLevel()}"
                    );
                }
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $this->logger->info("{$updatedCount} pets ont eu leur faim augmentée.");
        }

        return $updatedCount;
    }

    /**
     * Version alternative : calcul à la volée basé sur le temps écoulé depuis la dernière nourriture
     * (plus précis, mais plus coûteux si appelé souvent)
     */
    public function updateHungerBasedOnTime(Pet $pet): void
    {
        // À implémenter seulement si tu ajoutes un champ lastFedAt dans l'entité Pet
        /*
        if (!$pet->getLastFedAt()) {
            return;
        }

        $minutesSinceLastFed = (new \DateTime())->getTimestamp() - $pet->getLastFedAt()->getTimestamp();
        $hoursSinceLastFed = $minutesSinceLastFed / 3600;

        // Exemple : +1.5 faim par heure, + bonus par niveau
        $increase = (int) ($hoursSinceLastFed * (1.5 + ($pet->getLevel() * 0.15)));
        $newHunger = min(100, $pet->getHunger() + $increase);

        if ($newHunger > $pet->getHunger()) {
            $pet->setHunger($newHunger);
            $this->entityManager->flush();
        }
        */
    }

    /**
     * Méthode pour mettre à jour TOUS les pets en une seule passe (cron)
     * Alternative à increaseHungerForAll() si tu veux la version "temps écoulé"
     */
    public function updateAllHungerBasedOnTime(): int
    {
        // À utiliser seulement si tu ajoutes lastFedAt
        // ...
        return 0;
    }
}