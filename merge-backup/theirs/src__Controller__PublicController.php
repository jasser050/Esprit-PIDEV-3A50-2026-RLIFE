<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSettings;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, EmailService $emailService, MailerInterface $mailer): Response
    {
        $errors = [];
        $validFields = [];
        $submitted = false;
        
        if ($request->isMethod('POST')) {
            $submitted = true;
            // Get form data - Step 1
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $username = $request->request->get('username');
            $terms = $request->request->get('terms');
            
            // Get form data - Step 2
            $gender = $request->request->get('gender', 'male');
            $studyLevel = $request->request->get('study_level', 'beginner');
            $weeklyGoal = $request->request->get('weekly_goal', 5);
            $interests = $request->request->all('interests') ?? [];
            $notifications = $request->request->get('notifications') ? true : false;
            
            // Server-side validation
            if (!$firstName || strlen($firstName) < 2) {
                $errors['first_name'] = 'First name must be at least 2 characters';
            } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $firstName)) {
                $errors['first_name'] = 'First name can only contain letters, spaces, hyphens and apostrophes';
            } else {
                $validFields[] = 'first_name';
            }
            
            if (!$lastName || strlen($lastName) < 2) {
                $errors['last_name'] = 'Last name must be at least 2 characters';
            } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $lastName)) {
                $errors['last_name'] = 'Last name can only contain letters, spaces, hyphens and apostrophes';
            } else {
                $validFields[] = 'last_name';
            }
            
            if (!$username || strlen($username) < 3) {
                $errors['username'] = 'Username must be at least 3 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors['username'] = 'Username can only contain letters, numbers and underscores';
            } else {
                $existingUsername = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
                if ($existingUsername) {
                    $errors['username'] = 'This username is already taken';
                } else {
                    $validFields[] = 'username';
                }
            }
            
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address';
            } else {
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $errors['email'] = 'An account with this email already exists';
                } else {
                    $validFields[] = 'email';
                }
            }
            
            if (!$password || strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
                $errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
            } else {
                $validFields[] = 'password';
            }
            
            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'Password confirmation does not match';
            } else {
                $validFields[] = 'confirm_password';
            }
            
            if (!$terms) {
                $errors['terms'] = 'You must agree to the terms and conditions';
            } else {
                $validFields[] = 'terms';
            }
            
            if (!$gender || !in_array($gender, ['male', 'female'])) {
                $errors['gender'] = 'Please select a valid gender';
            } else {
                $validFields[] = 'gender';
            }
            
            // If no errors, create user
            if (empty($errors)) {
                try {
                    // Create new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setFirstName($firstName);
                    $user->setLastName($lastName);
                    $user->setUsername($username);
                    $user->setGender($gender);
                    
                    // Assign avatar based on gender
                    $avatarType = $gender === 'female' ? 'female_avatar_1' : 'male_avatar_1';
                    $user->setAvatarType($avatarType);
                    
                    // Hash password
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                    
                    // Generate verification token
                    $verificationToken = bin2hex(random_bytes(32));
                    $user->setVerificationToken($verificationToken);
                    $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
                    $user->setIsVerified(false);
                    
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
                    
                    // Save face descriptor if provided (Face ID registration)
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
                }
            }
        }
        
        return $this->render('pages/auth/register.html.twig', [
            'errors' => $errors,
            'validFields' => $validFields,
            'submitted' => $submitted,
            'old' => $request->request->all()
        ]);
    }

    #[Route('/welcome', name: 'app_welcome')]
    public function welcome(): Response
    {
        return $this->render('pages/auth/welcome.html.twig');
    }

    /**
     * Generate HTML for welcome email
     */
    private function getWelcomeEmailHtml(User $user): string
    {
        return sprintf('
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%);
                        color: white;
                        padding: 40px 30px;
                        border-radius: 10px 10px 0 0;
                        text-align: center;
                    }
                    .content {
                        background: #f9fafb;
                        padding: 30px;
                        border-radius: 0 0 10px 10px;
                    }
                    .message {
                        background: white;
                        padding: 25px;
                        border-radius: 8px;
                        border-left: 4px solid #667eea;
                        margin: 20px 0;
                    }
                    .features {
                        background: white;
                        padding: 20px;
                        border-radius: 8px;
                        margin: 20px 0;
                    }
                    .features h3 {
                        color: #667eea;
                        margin-top: 0;
                    }
                    .features ul {
                        list-style: none;
                        padding: 0;
                    }
                    .features li {
                        padding: 8px 0;
                        border-bottom: 1px solid #e5e7eb;
                    }
                    .features li:last-child {
                        border-bottom: none;
                    }
                    .features li:before {
                        content: "✓ ";
                        color: #667eea;
                        font-weight: bold;
                        margin-right: 8px;
                    }
                    .button {
                        display: inline-block;
                        background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%);
                        color: white;
                        padding: 15px 30px;
                        text-decoration: none;
                        border-radius: 8px;
                        margin: 20px 0;
                        font-weight: bold;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        color: #6b7280;
                        font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1 style="margin: 0; font-size: 32px;">Welcome to RLIFE! 🎉</h1>
                    <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 16px;">Student Life Management Platform</p>
                </div>
                <div class="content">
                    <div class="message">
                        <h2 style="color: #667eea; margin-top: 0;">Hello %s! 👋</h2>
                        <p>Thank you for joining RLIFE! We are excited to have you as part of our community.</p>
                        <p>Your account has been successfully created and you can now start managing your student life more effectively.</p>
                    </div>
                    
                    <div class="features">
                        <h3>🚀 What You Can Do with RLIFE:</h3>
                        <ul>
                            <li><strong>Organize Your Schedule:</strong> Plan your study sessions and manage your time effectively</li>
                            <li><strong>Manage Projects:</strong> Create and track your academic projects and assignments</li>
                            <li><strong>Study with Flashcards:</strong> Create decks and master your subjects with spaced repetition</li>
                            <li><strong>Track Your Progress:</strong> Monitor your academic performance and stay motivated</li>
                            <li><strong>Stay Organized:</strong> Keep all your academic materials in one place</li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="http://localhost:8000/login" class="button">Start Using RLIFE</a>
                    </div>
                    
                    <div class="message" style="background: #fef3c7; border-left-color: #f59e0b;">
                        <p style="margin: 0;"><strong>💡 Pro Tip:</strong> Complete your profile and set your study goals to get the most out of RLIFE!</p>
                    </div>
                    
                    <div class="footer">
                        <p>If you have any questions, feel free to reach out to our support team.</p>
                        <p style="margin-top: 15px;">Happy studying! 📚</p>
                        <p style="margin-top: 20px;">&copy; %d RLIFE. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ', $user->getFirstName(), date('Y'));
    }

    /**
     * Generate HTML for ban notification email
     */
    private function getBanEmailHtml(User $user, string $reason = null): string
    {
        $reasonText = $reason ? $reason : 'Violation of our Terms of Service';
        
        return sprintf('
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        background: linear-gradient(135deg, #ef4444 0%%, #dc2626 100%%);
                        color: white;
                        padding: 30px;
                        border-radius: 10px 10px 0 0;
                        text-align: center;
                    }
                    .content {
                        background: #f9fafb;
                        padding: 30px;
                        border-radius: 0 0 10px 10px;
                    }
                    .message {
                        background: white;
                        padding: 25px;
                        border-radius: 8px;
                        border-left: 4px solid #ef4444;
                        margin: 20px 0;
                    }
                    .warning-box {
                        background: #fef2f2;
                        border: 2px solid #ef4444;
                        padding: 20px;
                        border-radius: 8px;
                        margin: 20px 0;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        color: #6b7280;
                        font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1 style="margin: 0; font-size: 28px;">⚠️ Account Suspended</h1>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;">RLIFE Platform</p>
                </div>
                <div class="content">
                    <div class="message">
                        <h2 style="color: #ef4444; margin-top: 0;">Dear %s,</h2>
                        <p>We are writing to inform you that your RLIFE account has been temporarily suspended.</p>
                    </div>
                    
                    <div class="warning-box">
                        <h3 style="color: #ef4444; margin-top: 0;">🚫 Reason for Suspension:</h3>
                        <p style="font-size: 16px;"><strong>%s</strong></p>
                    </div>
                    
                    <div class="message">
                        <h3>What This Means:</h3>
                        <ul>
                            <li>You will not be able to access your RLIFE account</li>
                            <li>Your data remains secure and will not be deleted</li>
                            <li>This action may be temporary or permanent depending on the situation</li>
                        </ul>
                    </div>
                    
                    <div class="message" style="background: #fef3c7; border-left-color: #f59e0b;">
                        <h3 style="margin-top: 0;">📞 Need Help?</h3>
                        <p>If you believe this is a mistake or would like to appeal this decision, please contact our support team immediately.</p>
                        <p><strong>Support Email:</strong> jasserbalti555@gmail.com</p>
                    </div>
                    
                    <div class="footer">
                        <p>Please review our Terms of Service and Community Guidelines.</p>
                        <p style="margin-top: 20px;">&copy; %d RLIFE. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ', $user->getFirstName(), $reasonText, date('Y'));
    }
}
