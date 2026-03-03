<?php

namespace App\Service;

use App\Entity\Pet;
use App\Entity\User;
use App\Pet\Behavior\PetBehaviorFactory;

class PetAiService
{
    public function __construct(private readonly PetBehaviorFactory $behaviorFactory)
    {
    }

    /**
     * @return array{message:string,recommended_action:string,behavior:string,mood:string}
     */
    public function buildPetInsight(User $user, Pet $pet): array
    {
        $strategy = $this->behaviorFactory->forPet($pet);
        $messages = $strategy->moodMessages($pet);
        $activityScore = $this->estimateUserActivityScore($user);
        $mood = mb_strtolower($pet->getMood());

        $recommended = 'play';
        if ($pet->getHealth() <= 45) {
            $recommended = 'heal';
        } elseif ($pet->getEnergy() <= 30) {
            $recommended = 'sleep';
        } elseif ($pet->getHunger() >= 75) {
            $recommended = 'feed';
        } elseif ($activityScore >= 65) {
            $recommended = 'play';
        }

        $message = $messages[array_rand($messages)];
        if ($recommended === 'heal') {
            $message = 'I feel weak. A heal action would help now.';
        } elseif ($recommended === 'sleep') {
            $message = 'Energy is low. Let me rest for a bit.';
        } elseif ($recommended === 'feed') {
            $message = 'I need food to keep my stats stable.';
        } elseif ($activityScore >= 70) {
            $message = 'Your productivity is high today. Let us push for bonus XP.';
        }

        return [
            'message' => $message,
            'recommended_action' => $recommended,
            'behavior' => $pet->getPersonality(),
            'mood' => $mood,
        ];
    }

    private function estimateUserActivityScore(User $user): int
    {
        $projects = $user->getProjects();
        $assignments = $user->getAssignments();
        $openAssignments = 0;
        $doneAssignments = 0;
        foreach ($assignments as $assignment) {
            $status = mb_strtolower(trim((string) $assignment->getStatut()));
            if (in_array($status, ['termine', 'completed', 'done'], true)) {
                $doneAssignments++;
            } else {
                $openAssignments++;
            }
        }

        $score = 30;
        $score += min(35, $doneAssignments * 4);
        $score += min(20, $projects->count() * 2);
        $score -= min(18, $openAssignments);

        return max(5, min(100, $score));
    }
}

