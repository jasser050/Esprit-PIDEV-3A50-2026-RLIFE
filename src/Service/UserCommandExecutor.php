<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserSettings;
use App\Entity\ChatMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCommandExecutor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Execute a user management command
     *
     * @param array $command Command structure from AI
     * @return array Result with success status and data
     */
    public function execute(array $command): array
    {
        $action = $command['action'] ?? null;
        $params = $command['params'] ?? [];

        // Validate action
        if (empty($action)) {
            return [
                'success' => false,
                'message' => '❌ No action specified. Please tell me what you want to do (create, read, update, or delete a user).',
                'result' => null,
            ];
        }

        $action = strtoupper($action);

        return match ($action) {
            'CREATE' => $this->createUser($params),
            'READ' => $this->readUsers($params),
            'UPDATE' => $this->updateUser($params),
            'DELETE' => $this->deleteUser($params),
            default => [
                'success' => false,
                'message' => "❌ Unknown action '{$action}'. I can only CREATE, READ, UPDATE, or DELETE users. Please rephrase your command.",
                'result' => null,
            ],
        };
    }

    /**
     * Create a new user with full registration support
     */
    private function createUser(array $params): array
    {
        try {
            $user = new User();
            
            // Required fields
            $firstName = $params['firstName'] ?? throw new \Exception('firstName is required');
            $lastName = $params['lastName'] ?? throw new \Exception('lastName is required');
            $email = $params['email'] ?? throw new \Exception('email is required');
            $username = $params['username'] ?? strtolower(preg_replace('/[^a-z0-9]/', '', $firstName . $lastName));
            $plainPassword = $params['password'] ?? 'password123';
            $gender = $params['gender'] ?? 'other';

            // Check if email already exists
            if ($this->userRepository->findOneBy(['email' => $email])) {
                return [
                    'success' => false,
                    'message' => "❌ Email {$email} is already registered",
                    'result' => null,
                ];
            }

            // Check if username already exists
            if ($this->userRepository->findOneBy(['username' => $username])) {
                // Generate unique username
                $username = $username . rand(100, 999);
            }

            // Basic user info
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setGender($gender);
            
            // Avatar type - smart default based on gender
            $avatarType = $params['avatarType'] ?? $this->getDefaultAvatar($gender);
            $user->setAvatarType($avatarType);
            
            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Optional profile fields
            if (isset($params['phoneNumber'])) {
                $user->setPhoneNumber($params['phoneNumber']);
            }
            if (isset($params['university'])) {
                $user->setUniversity($params['university']);
            }
            if (isset($params['studentId'])) {
                $user->setStudentId($params['studentId']);
            }
            if (isset($params['bio'])) {
                $user->setBio($params['bio']);
            }

            // Auto-verify user (created by admin)
            $user->setIsVerified(true);
            
            // Generate verification token (just in case)
            $user->setVerificationToken(bin2hex(random_bytes(32)));
            $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

            // Create UserSettings with smart defaults
            $settings = new UserSettings();
            $settings->setUser($user);
            $settings->setStudyLevel($params['studyLevel'] ?? 'beginner');
            $settings->setWeeklyGoal($params['weeklyGoal'] ?? 5);
            $settings->setInterests($params['interests'] ?? []);
            $settings->setNotificationEnabled($params['notifications'] ?? true);
            $settings->setEmailNotifications($params['notifications'] ?? true);
            $settings->setThemePreference('light');
            $settings->setLanguage('en');
            
            $user->setSettings($settings);

            // Persist both entities
            $this->entityManager->persist($user);
            $this->entityManager->persist($settings);
            
            // CRITICAL: Flush to database
            try {
                $this->entityManager->flush();
                
                // Verify user was actually saved by checking ID
                if (!$user->getId()) {
                    throw new \Exception('User was not saved to database - ID is null after flush');
                }
                
            } catch (\Doctrine\DBAL\Exception $e) {
                // Database constraint error
                throw new \Exception('Database constraint error: ' . $e->getMessage());
            } catch (\Exception $e) {
                // Other errors
                throw new \Exception('Error saving to database: ' . $e->getMessage());
            }

            // Get user data before any entity manager operations
            $userId = $user->getId();
            $userArray = $this->userToArray($user);

            // Build success message with details
            $details = [
                "✓ User created successfully!",
                "ID: {$user->getId()}",
                "Name: {$user->getFullName()}",
                "Email: {$email}",
                "Username: {$username}",
                "Avatar: {$avatarType}",
                "Gender: {$gender}",
            ];

            if (isset($params['university'])) {
                $details[] = "University: {$params['university']}";
            }

            if (isset($params['studyLevel']) || isset($params['weeklyGoal']) || isset($params['interests'])) {
                $details[] = "Study Level: " . ($params['studyLevel'] ?? 'beginner');
                $details[] = "Weekly Goal: " . ($params['weeklyGoal'] ?? 5) . " hours";
                if (!empty($params['interests'])) {
                    $details[] = "Interests: " . implode(', ', $params['interests']);
                }
            }

            $details[] = "🔑 Login credentials: {$email} / {$plainPassword}";
            $details[] = "\n✅ User #{$userId} is now in database and ready to login!";

            return [
                'success' => true,
                'message' => implode("\n", $details),
                'result' => $userArray,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '❌ Error creating user: ' . $e->getMessage(),
                'result' => null,
            ];
        }
    }

    /**
     * Get default avatar based on gender
     */
    private function getDefaultAvatar(string $gender): string
    {
        return match(strtolower($gender)) {
            'male' => 'male-avatar.glb',
            'female' => 'female-avatar.glb',
            default => 'male-avatar.glb',
        };
    }

    /**
     * Get display name for avatar filename
     */
    private function getAvatarDisplayName(string $filename): string
    {
        return match($filename) {
            // Male avatars
            'male-avatar.glb' => 'Classic (Male)',
            'male-avatar1.glb' => 'Explorer (Male)',
            'male-avatar2.glb' => 'Scholar (Male)',
            'male-avatar3.glb' => 'Athlete (Male)',
            'male-avatar4.glb' => 'Artist (Male)',
            'male-avatar5.glb' => 'Tech (Male)',
            'male-avatar6.glb' => 'Creative (Male)',
            'male-avatar7.glb' => 'Leader (Male)',
            // Female avatars
            'female-avatar.glb' => 'Classic (Female)',
            'female-avatar2.glb' => 'Elegant (Female)',
            'female-avatar3.glb' => 'Professional (Female)',
            default => $filename,
        };
    }

    /**
     * Read/search users
     */
    private function readUsers(array $params): array
    {
        try {
            $filter = $params['filter'] ?? 'all';
            $limit = $params['limit'] ?? 20;

            $queryBuilder = $this->userRepository->createQueryBuilder('u');

            // Apply filters
            if ($filter === 'banned') {
                $queryBuilder->where('u.isBanned = true');
            } elseif ($filter === 'active') {
                $queryBuilder->where('u.isBanned = false');
            } elseif ($filter === 'admin') {
                $queryBuilder->where('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_ADMIN%');
            } elseif (isset($params['email'])) {
                $queryBuilder->where('u.email LIKE :email')
                    ->setParameter('email', '%' . $params['email'] . '%');
            } elseif (isset($params['name'])) {
                $queryBuilder->where('u.firstName LIKE :name OR u.lastName LIKE :name')
                    ->setParameter('name', '%' . $params['name'] . '%');
            } elseif (isset($params['university'])) {
                $queryBuilder->where('u.university = :university')
                    ->setParameter('university', $params['university']);
            } elseif (isset($params['id'])) {
                $user = $this->userRepository->find($params['id']);
                if (!$user) {
                    return [
                        'success' => false,
                        'message' => 'User not found with ID: ' . $params['id'],
                        'result' => null,
                    ];
                }
                return [
                    'success' => true,
                    'message' => 'User found',
                    'result' => [$this->userToArray($user)],
                ];
            }

            $users = $queryBuilder
                ->orderBy('u.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $usersArray = array_map(fn(User $u) => $this->userToArray($u), $users);

            $count = count($usersArray);
            $filterText = $filter === 'all' ? 'users' : "{$filter} users";

            return [
                'success' => true,
                'message' => "✓ Found {$count} {$filterText}",
                'result' => $usersArray,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error reading users: ' . $e->getMessage(),
                'result' => null,
            ];
        }
    }

    /**
     * Update a user
     */
    private function updateUser(array $params): array
    {
        try {
            $userId = $params['id'] ?? throw new \Exception('User ID is required');
            
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => "User not found with ID: {$userId}",
                    'result' => null,
                ];
            }

            $updated = [];

            // Update fields
            if (isset($params['firstName'])) {
                $user->setFirstName($params['firstName']);
                $updated[] = 'firstName';
            }
            if (isset($params['lastName'])) {
                $user->setLastName($params['lastName']);
                $updated[] = 'lastName';
            }
            if (isset($params['email'])) {
                // Check if new email already exists
                $existing = $this->userRepository->findOneBy(['email' => $params['email']]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    return [
                        'success' => false,
                        'message' => "Email {$params['email']} is already taken by another user",
                        'result' => null,
                    ];
                }
                $user->setEmail($params['email']);
                $updated[] = 'email';
            }
            if (isset($params['username'])) {
                // Check if new username already exists
                $existing = $this->userRepository->findOneBy(['username' => $params['username']]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    return [
                        'success' => false,
                        'message' => "Username {$params['username']} is already taken",
                        'result' => null,
                    ];
                }
                $user->setUsername($params['username']);
                $updated[] = 'username';
            }
            if (isset($params['password'])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $params['password']);
                $user->setPassword($hashedPassword);
                $updated[] = 'password';
            }
            if (isset($params['phoneNumber'])) {
                $user->setPhoneNumber($params['phoneNumber']);
                $updated[] = 'phoneNumber';
            }
            if (isset($params['university'])) {
                $user->setUniversity($params['university']);
                $updated[] = 'university';
            }
            if (isset($params['studentId'])) {
                $user->setStudentId($params['studentId']);
                $updated[] = 'studentId';
            }
            if (isset($params['bio'])) {
                $user->setBio($params['bio']);
                $updated[] = 'bio';
            }
            if (isset($params['gender'])) {
                $user->setGender($params['gender']);
                $updated[] = 'gender';
            }
            if (isset($params['avatarType'])) {
                $user->setAvatarType($params['avatarType']);
                $updated[] = 'avatarType';
            }

            if (empty($updated)) {
                return [
                    'success' => false,
                    'message' => 'No fields to update',
                    'result' => null,
                ];
            }

            $this->entityManager->flush();

            // Build detailed success message
            $details = ["✓ User #{$userId} ({$user->getFullName()}) updated successfully!"];
            
            foreach ($updated as $field) {
                if ($field === 'avatarType') {
                    $avatarName = $this->getAvatarDisplayName($user->getAvatarType());
                    $details[] = "Avatar changed to: {$avatarName}";
                } elseif ($field === 'password') {
                    $details[] = "Password updated";
                } elseif ($field === 'email') {
                    $details[] = "Email: {$user->getEmail()}";
                } elseif ($field === 'username') {
                    $details[] = "Username: {$user->getUsername()}";
                } else {
                    $details[] = ucfirst($field) . " updated";
                }
            }

            return [
                'success' => true,
                'message' => implode("\n", $details),
                'result' => $this->userToArray($user),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage(),
                'result' => null,
            ];
        }
    }

    /**
     * Delete a user by ID, name, or email
     */
    private function deleteUser(array $params): array
    {
        try {
            $user = null;
            
            // Try to find user by ID first
            if (isset($params['id'])) {
                $user = $this->userRepository->find($params['id']);
            }
            // Try to find by full name
            elseif (isset($params['firstName']) && isset($params['lastName'])) {
                $users = $this->userRepository->createQueryBuilder('u')
                    ->where('u.firstName = :firstName')
                    ->andWhere('u.lastName = :lastName')
                    ->setParameter('firstName', $params['firstName'])
                    ->setParameter('lastName', $params['lastName'])
                    ->getQuery()
                    ->getResult();
                
                if (count($users) > 1) {
                    $names = array_map(fn($u) => "#{$u->getId()} {$u->getFullName()} ({$u->getEmail()})", $users);
                    return [
                        'success' => false,
                        'message' => "❌ Multiple users found with that name:\n" . implode("\n", $names) . "\n\nPlease specify the ID or email to delete the correct user.",
                        'result' => null,
                    ];
                }
                
                $user = $users[0] ?? null;
            }
            // Try to find by email
            elseif (isset($params['email'])) {
                $user = $this->userRepository->findOneBy(['email' => $params['email']]);
            }
            // Try to find by name (single field)
            elseif (isset($params['name'])) {
                $nameParts = explode(' ', $params['name'], 2);
                if (count($nameParts) === 2) {
                    return $this->deleteUser([
                        'firstName' => $nameParts[0],
                        'lastName' => $nameParts[1]
                    ]);
                }
            }
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => '❌ User not found. Please provide ID, full name, or email.',
                    'result' => null,
                ];
            }

            $userId = $user->getId();
            $userName = $user->getFullName();
            $userEmail = $user->getEmail();

            // CRITICAL: Delete all chat messages for this user FIRST to avoid foreign key constraint
            $chatMessages = $this->entityManager->getRepository(ChatMessage::class)
                ->findBy(['user' => $user]);
            
            $chatCount = count($chatMessages);
            foreach ($chatMessages as $chatMessage) {
                $this->entityManager->remove($chatMessage);
            }
            
            // Flush chat message deletions first
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                throw new \Exception('Failed to delete user chat history: ' . $e->getMessage());
            }

            // Now remove user (related entities will cascade if configured)
            $this->entityManager->remove($user);
            
            // Force flush to database
            try {
                $this->entityManager->flush();
                
                // Clear entity manager to force fresh data
                $this->entityManager->clear();
                
                // Verify user was actually deleted
                $checkUser = $this->userRepository->find($userId);
                if ($checkUser) {
                    throw new \Exception('User still exists in database after delete attempt - foreign key constraint may be blocking deletion');
                }
                
            } catch (\Doctrine\DBAL\Exception $e) {
                throw new \Exception('Database constraint error during delete: ' . $e->getMessage() . ' - User may have related records preventing deletion');
            } catch (\Exception $e) {
                throw new \Exception('Database error during delete: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => "✓ User deleted successfully!\n🗑️ Removed: {$userName} (ID: {$userId})\n📧 Email: {$userEmail}\n💬 Deleted {$chatCount} chat messages\n\n✅ User has been permanently removed from database.",
                'result' => null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '❌ Error deleting user: ' . $e->getMessage(),
                'result' => null,
            ];
        }
    }

    /**
     * Convert User entity to array
     */
    private function userToArray(User $user): array
    {
        $data = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'phoneNumber' => $user->getPhoneNumber(),
            'gender' => $user->getGender(),
            'avatarType' => $user->getAvatarType(),
            'bio' => $user->getBio(),
            'studentId' => $user->getStudentId(),
            'university' => $user->getUniversity(),
            'isBanned' => $user->isBanned(),
            'isAdmin' => $user->isAdmin(),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];

        // Add settings if available
        $settings = $user->getSettings();
        if ($settings) {
            $data['settings'] = [
                'studyLevel' => $settings->getStudyLevel(),
                'weeklyGoal' => $settings->getWeeklyGoal(),
                'interests' => $settings->getInterests(),
                'notifications' => $settings->isNotificationEnabled(),
            ];
        }

        return $data;
    }
}
