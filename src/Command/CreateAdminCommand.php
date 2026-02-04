<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user for the system',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Create Admin User');

        // Ask for user details
        $email = $io->ask('Email', 'admin@studyflow.com');
        $password = $io->askHidden('Password (hidden)', null, function ($password) {
            if (empty($password)) {
                throw new \RuntimeException('Password cannot be empty.');
            }
            return $password;
        });
        $firstName = $io->ask('First Name', 'Admin');
        $lastName = $io->ask('Last Name', 'User');
        $username = $io->ask('Username', 'admin');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error('A user with this email already exists!');
            return Command::FAILURE;
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername($username);
        $user->setGender('male');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Create default settings
        $settings = new UserSettings();
        $settings->setUser($user);
        $user->setSettings($settings);

        // Save to database
        $this->entityManager->persist($user);
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        $io->success([
            'Admin user created successfully!',
            'Email: ' . $email,
            'You can now login with these credentials.',
        ]);

        return Command::SUCCESS;
    }
}
