<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class PublicController extends AbstractController
{
    #[Route('/', name: 'app_landing')]
    public function landing(): Response
    {
        // Just show the landing page - don't redirect
        return $this->render('pages/landing.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('pages/auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        
        if ($request->isMethod('POST')) {
            // Get form data - Step 1
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $username = $request->request->get('username');
            
            // Get form data - Step 2
            $gender = $request->request->get('gender', 'male');
            $studyLevel = $request->request->get('study_level', 'beginner');
            $weeklyGoal = $request->request->get('weekly_goal', 5);
            $interests = $request->request->all('interests') ?? [];
            $notifications = $request->request->get('notifications') ? true : false;
            
            // Validate required fields
            if (!$email || !$password || !$firstName || !$lastName || !$username) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->redirectToRoute('app_register');
            }
            
            // Check if user already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->redirectToRoute('app_register');
            }
            
            // Check if username already exists
            $existingUsername = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUsername) {
                $this->addFlash('error', 'This username is already taken. Please choose another one.');
                return $this->redirectToRoute('app_register');
            }
            
            try {
                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setUsername($username);
                $user->setGender($gender);
                
                // Hash password
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                
                // Create user settings with preferences
                $settings = new UserSettings();
                $settings->setUser($user);
                $settings->setStudyLevel($studyLevel);
                $settings->setWeeklyGoal((int)$weeklyGoal);
                $settings->setInterests($interests);
                $settings->setNotificationEnabled($notifications);
                $settings->setEmailNotifications($notifications);
                $settings->setThemePreference('light');
                $settings->setLanguage('en');
                
                $user->setSettings($settings);
                
                // Save user
                $entityManager->persist($user);
                $entityManager->persist($settings);
                $entityManager->flush();
                
                $this->addFlash('success', 'Account created successfully! Please log in.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
                return $this->redirectToRoute('app_register');
            }
        }
        
        return $this->render('pages/auth/register.html.twig');
    }

    #[Route('/welcome', name: 'app_welcome')]
    public function welcome(): Response
    {
        return $this->render('pages/auth/welcome.html.twig');
    }
}
