<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VerificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailService $emailService
    ) {}

    #[Route('/verify/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token): Response
    {
        // Find user by verification token
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'verificationToken' => $token
        ]);

        // Token not found
        if (!$user) {
            $this->addFlash('error', 'Invalid verification link. Please try again or contact support.');
            return $this->render('pages/auth/verification_error.html.twig', [
                'error_type' => 'invalid_token'
            ]);
        }

        // Token expired
        if ($user->isVerificationTokenExpired()) {
            $this->addFlash('error', 'This verification link has expired. We\'ll send you a new one.');
            
            // Generate new token
            $newToken = bin2hex(random_bytes(32));
            $user->setVerificationToken($newToken);
            $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
            
            $this->entityManager->flush();
            
            // Resend verification email
            $verificationUrl = $this->generateUrl('app_verify_email', ['token' => $newToken], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            $this->emailService->sendVerificationEmail($user, $verificationUrl);
            
            return $this->render('pages/auth/verification_error.html.twig', [
                'error_type' => 'expired',
                'email_resent' => true
            ]);
        }

        // Already verified
        if ($user->isVerified()) {
            $this->addFlash('info', 'Your account is already verified! You can log in.');
            return $this->redirectToRoute('app_login');
        }

        // Verify the user
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);
        
        $this->entityManager->flush();

        // Send welcome email
        try {
            $this->emailService->sendWelcomeEmail($user);
        } catch (\Exception $e) {
            // Log error but don't fail verification
        }

        $this->addFlash('success', 'Your account has been verified successfully! You can now log in.');
        
        return $this->render('pages/auth/verification_success.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): Response
    {
        $email = $request->request->get('email');
        
        if (!$email) {
            $this->addFlash('error', 'Please provide your email address.');
            return $this->redirectToRoute('app_login');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            // Don't reveal if email exists or not for security
            $this->addFlash('success', 'If an account exists with this email, a verification link has been sent.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your account is already verified.');
            return $this->redirectToRoute('app_login');
        }

        // Generate new token
        $token = bin2hex(random_bytes(32));
        $user->setVerificationToken($token);
        $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
        
        $this->entityManager->flush();

        // Send verification email
        $verificationUrl = $this->generateUrl('app_verify_email', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $this->emailService->sendVerificationEmail($user, $verificationUrl);

        $this->addFlash('success', 'Verification email has been sent! Please check your inbox.');
        return $this->redirectToRoute('app_login');
    }
}
