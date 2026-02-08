<?php

namespace App\Twig;

use App\Repository\DeckRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DeckExtension extends AbstractExtension
{
    public function __construct(
        private DeckRepository $deckRepository
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('deck_count', [$this, 'getDeckCount']),
        ];
    }

    public function getDeckCount(): int
    {
        return count($this->deckRepository->findAll());
    }
}
