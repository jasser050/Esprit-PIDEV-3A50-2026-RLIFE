<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\AssignmentRepository;
use App\Repository\DeckRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DeckExtension extends AbstractExtension
{
    public function __construct(
        private DeckRepository $deckRepository,
        private AssignmentRepository $assignmentRepository,
        private ProjectRepository $projectRepository,
        private Security $security
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('deck_count', [$this, 'getDeckCount']),
            new TwigFunction('assignment_count', [$this, 'getAssignmentCount']),
            new TwigFunction('project_count', [$this, 'getProjectCount']),
        ];
    }

    public function getDeckCount(): int
    {
        return count($this->deckRepository->findAll());
    }

    public function getAssignmentCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return $this->assignmentRepository->countByUser($user);
    }

    public function getProjectCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return $this->projectRepository->countByUser($user);
    }
}
