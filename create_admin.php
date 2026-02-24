<?php

require __DIR__.'/vendor/autoload.php';

use App\Entity\User;
use App\Entity\UserSettings;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine')->getManager();
$passwordHasher = $container->get('Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface');

// Admin credentials
$email = 'admin@studyflow.com';
$password = 'admin123';

// Check if admin already exists
$existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

if ($existingUser) {
    echo "Admin user already exists!\n";
    exit(0);
}

// Create admin user
$user = new User();
$user->setEmail($email);
$user->setFirstName('Admin');
$user->setLastName('User');
$user->setUsername('admin');
$user->setGender('male');
$user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

$hashedPassword = $passwordHasher->hashPassword($user, $password);
$user->setPassword($hashedPassword);

// Create settings
$settings = new UserSettings();
$settings->setUser($user);
$user->setSettings($settings);

$entityManager->persist($user);
$entityManager->persist($settings);
$entityManager->flush();

echo "✅ Admin user created successfully!\n";
echo "Email: admin@studyflow.com\n";
echo "Password: admin123\n";
echo "\nYou can now login at: http://localhost/login\n";
