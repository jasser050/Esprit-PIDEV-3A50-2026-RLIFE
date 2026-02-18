<?php

namespace App\Command;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Repository\AssignmentRepository;
use App\Repository\ProjectRepository;
use App\Service\NotificationManager;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:check-deadlines',
    description: 'Send in-app and email reminders for assignments/projects due tomorrow',
)]
class CheckDeadlinesCommand extends Command
{
    public function __construct(
        private readonly AssignmentRepository $assignmentRepo,
        private readonly ProjectRepository $projectRepo,
        private readonly NotificationManager $notificationManager,
        private readonly NotificationService $notificationService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Deadline check for tomorrow reminders');

        $today = new \DateTime('today');
        $tomorrow = (clone $today)->modify('+1 day');
        $dayAfterTomorrow = (clone $today)->modify('+2 days');

        $updatedCount = 0;

        $tasks = $this->assignmentRepo->createQueryBuilder('a')
            ->where('a.dateFin BETWEEN :tomorrow AND :dayAfterTomorrow')
            ->andWhere('a.statut != :done')
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('dayAfterTomorrow', $dayAfterTomorrow)
            ->setParameter('done', 'Terminé')
            ->getQuery()
            ->getResult();

        foreach ($tasks as $task) {
            /** @var Assignment $task */
            $user = $task->getUser();
            if (!$user) {
                continue;
            }

            $this->notificationManager->createNotification(
                $user,
                'Task due tomorrow',
                sprintf('"%s" is due tomorrow (%s).', $task->getTitre(), $task->getDateFin()?->format('d/m/Y')),
                'deadline_task',
                $this->urlGenerator->generate('app_assignments_show', ['id' => $task->getId()])
            );

            if ($user->getSettings() && $user->getSettings()->isEmailNotifications()) {
                $this->notificationService->notifyTaskDeadline($task);
            }

            $updatedCount++;
        }

        $projects = $this->projectRepo->createQueryBuilder('p')
            ->where('p.dateFin BETWEEN :tomorrow AND :dayAfterTomorrow')
            ->andWhere('p.statut != :done')
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('dayAfterTomorrow', $dayAfterTomorrow)
            ->setParameter('done', 'Terminé')
            ->getQuery()
            ->getResult();

        foreach ($projects as $project) {
            /** @var Project $project */
            $user = $project->getUser();
            if (!$user) {
                continue;
            }

            $this->notificationManager->createNotification(
                $user,
                'Project due tomorrow',
                sprintf('"%s" is due tomorrow (%s).', $project->getTitre(), $project->getDateFin()?->format('d/m/Y')),
                'deadline_project',
                $this->urlGenerator->generate('app_project_show', ['id' => $project->getId()])
            );

            if ($user->getSettings() && $user->getSettings()->isEmailNotifications()) {
                $this->notificationService->notifyProjectDeadline($project);
            }

            $updatedCount++;
        }

        $this->em->flush();
        $io->success(sprintf('%d reminder notification(s) sent.', $updatedCount));

        return Command::SUCCESS;
    }
}
