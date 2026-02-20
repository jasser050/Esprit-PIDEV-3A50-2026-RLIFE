<?php

namespace App\Command;

use App\Service\PetHungerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:pet:hunger-tick',
    description: 'Increase pet hunger progressively over time',
)]
class UpdatePetHungerCommand extends Command
{
    public function __construct(private readonly PetHungerService $petHungerService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $updated = $this->petHungerService->increaseHungerForAll();
        $io->success(sprintf('%d pet(s) updated.', $updated));

        return Command::SUCCESS;
    }
}
