<?php

namespace App\Command;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Repository\AssignmentRepository;
use App\Repository\ProjectRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-deadlines',
    description: 'Vérifie les échéances des projets et tâches et envoie des notifications si nécessaire'
)]
class CheckDeadlinesCommand extends Command
{
    public function __construct(
        private ProjectRepository $projectRepo,
        private AssignmentRepository $assignmentRepo,
        private NotificationService $notificationService,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Vérification des échéances - ' . date('d/m/Y H:i'));

        $today = new \DateTime('today');
        $tomorrow = (clone $today)->modify('+1 day');

        // === Projets proches de l'échéance ===
        $projects = $this->projectRepo->createQueryBuilder('p')
            ->where('p.dateFin BETWEEN :today AND :tomorrow')
            ->andWhere('p.statut != :termine')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('termine', 'Terminé')
            ->getQuery()
            ->getResult();

        $io->section('Projets proches de l\'échéance');
        $io->writeln(count($projects) . ' projet(s) concerné(s)');

        foreach ($projects as $project) {
            /** @var Project $project */
            $this->notificationService->notifyProjectDeadline($project);
            $io->success('Notification envoyée pour projet : ' . $project->getTitre());
        }

        // === Tâches (Assignments) proches de l'échéance ===
        $tasks = $this->assignmentRepo->createQueryBuilder('a')
            ->where('a.dateFin BETWEEN :today AND :tomorrow')
            ->andWhere('a.statut != :termine')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('termine', 'Terminé')
            ->getQuery()
            ->getResult();

        $io->section('Tâches proches de l\'échéance');
        $io->writeln(count($tasks) . ' tâche(s) concernée(s)');

        foreach ($tasks as $task) {
            /** @var Assignment $task */
            $this->notificationService->notifyTaskDeadline($task);
            $io->success('Notification envoyée pour tâche : ' . $task->getTitre());
        }

        $io->success('Vérification terminée.');

        return Command::SUCCESS;
    }
}