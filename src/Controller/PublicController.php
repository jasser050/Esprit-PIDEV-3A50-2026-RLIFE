<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSettings;
use App\Form\LoginFormType;
use App\Service\EmailService;
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
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // If already authenticated (including via remember-me cookie), skip login page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Create the form with PHP validation
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);
        
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('pages/auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, EmailService $emailService): Response
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
            $avatarType = $request->request->get('avatar_type');
            
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
                
                // Assign avatar (from selection or default by gender)
                if (!$avatarType) {
                    $avatarType = $gender === 'female' ? 'female-avatar.glb' : 'male-avatar.glb';
                }
                $user->setAvatarType($avatarType);
                
                // Hash password
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                
                // Generate verification token
                $verificationToken = bin2hex(random_bytes(32));
                $user->setVerificationToken($verificationToken);
                $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
                $user->setIsVerified(true);
                
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
                
                // Save face descriptor if provided
                $faceDescriptorRaw = $request->request->get('face_descriptor');
                if ($faceDescriptorRaw) {
                    $faceDescriptor = json_decode($faceDescriptorRaw, true);
                    if (is_array($faceDescriptor) && count($faceDescriptor) > 0) {
                        $user->setFaceDescriptor($faceDescriptor);
                    }
                }

                // Save user
                $entityManager->persist($user);
                $entityManager->persist($settings);
                $entityManager->flush();
                
                // Send verification email
                try {
                    $verificationUrl = $this->generateUrl('app_verify_email', ['token' => $verificationToken], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
                    $emailService->sendVerificationEmail($user, $verificationUrl);
                    
                    $this->addFlash('success', 'Account created! Please check your email to verify your account.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Account created, but we couldn\'t send the verification email. Please contact support.');
                }
                
                return $this->redirectToRoute('app_login', ['registered' => 'true', 'email' => $email]);
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
