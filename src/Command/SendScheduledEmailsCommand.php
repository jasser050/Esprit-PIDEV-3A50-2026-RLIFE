<?php

namespace App\Command;

use App\Repository\ScheduledEmailRepository;
use App\Service\AdminMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-scheduled-emails',
    description: 'Send scheduled emails that are due',
)]
class SendScheduledEmailsCommand extends Command
{
    private ScheduledEmailRepository $scheduledEmailRepository;
    private AdminMailerService $mailerService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ScheduledEmailRepository $scheduledEmailRepository,
        AdminMailerService $mailerService,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->scheduledEmailRepository = $scheduledEmailRepository;
        $this->mailerService = $mailerService;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('RLIFE Scheduled Email Sender');
        $io->info('Checking for scheduled emails that are due...');

        // Find emails that are scheduled and due to be sent
        $dueEmails = $this->scheduledEmailRepository->findDueEmails();

        if (empty($dueEmails)) {
            $io->success('No scheduled emails to send at this time.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d scheduled email(s) to send', count($dueEmails)));

        $successCount = 0;
        $failCount = 0;

        foreach ($dueEmails as $scheduledEmail) {
            $io->section(sprintf('Processing: %s', $scheduledEmail->getSubject()));
            
            try {
                // Send the email based on recipient type
                $sentCount = match ($scheduledEmail->getRecipientType()) {
                    'all' => $this->mailerService->sendToAllUsers(
                        $scheduledEmail->getSubject(),
                        $scheduledEmail->getMessage()
                    ),
                    'active' => $this->mailerService->sendToActiveUsers(
                        $scheduledEmail->getSubject(),
                        $scheduledEmail->getMessage()
                    ),
                    'banned' => $this->mailerService->sendToBannedUsers(
                        $scheduledEmail->getSubject(),
                        $scheduledEmail->getMessage()
                    ),
                    'admins' => $this->mailerService->sendToAdmins(
                        $scheduledEmail->getSubject(),
                        $scheduledEmail->getMessage()
                    ),
                    default => 0,
                };

                if ($sentCount > 0) {
                    // Mark as sent
                    $scheduledEmail->setStatus('sent');
                    $scheduledEmail->setSentAt(new \DateTime());
                    $scheduledEmail->setRecipientCount($sentCount);
                    
                    $this->entityManager->flush();
                    
                    $io->success(sprintf('✅ Sent to %d recipients', $sentCount));
                    $successCount++;
                } else {
                    // Mark as failed (no recipients)
                    $scheduledEmail->setStatus('failed');
                    $this->entityManager->flush();
                    
                    $io->warning('⚠️ No recipients found for this email');
                    $failCount++;
                }
            } catch (\Exception $e) {
                // Mark as failed
                $scheduledEmail->setStatus('failed');
                $this->entityManager->flush();
                
                $io->error(sprintf('❌ Failed: %s', $e->getMessage()));
                $failCount++;
            }
        }

        $io->newLine();
        $io->title('Summary');
        $io->table(
            ['Status', 'Count'],
            [
                ['✅ Successfully sent', $successCount],
                ['❌ Failed', $failCount],
                ['📧 Total processed', count($dueEmails)],
            ]
        );

        if ($successCount > 0) {
            $io->success(sprintf('Successfully sent %d scheduled email(s)!', $successCount));
        }

        if ($failCount > 0) {
            $io->warning(sprintf('%d scheduled email(s) failed to send', $failCount));
        }

        return Command::SUCCESS;
    }
}
