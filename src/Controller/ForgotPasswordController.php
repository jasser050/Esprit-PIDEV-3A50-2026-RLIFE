<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        EmailService $emailService
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));

            $user = $userRepository->findOneBy(['email' => $email]);

            // Always show success to avoid email enumeration
            if ($user && $user->isVerified()) {
                $token = bin2hex(random_bytes(32));
                $user->setResetPasswordToken($token);
                $user->setResetPasswordTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $em->flush();

                $resetUrl = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                try {
                    $emailService->sendPasswordResetEmail($user, $resetUrl);
                } catch (\Exception $e) {
                    // Silent — don't reveal whether the send failed
                }
            }

            $this->addFlash('success', 'If that email is registered, a reset link has been sent. Check your inbox.');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('pages/auth/forgot-password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user || $user->isResetPasswordTokenExpired()) {
            $this->addFlash('error', 'This reset link is invalid or has expired. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm_password', '');

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Password must be at least 8 characters.');
                return $this->render('pages/auth/reset-password.html.twig', ['token' => $token]);
            }

            if ($password !== $confirm) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->render('pages/auth/reset-password.html.twig', ['token' => $token]);
            }

            $user->setPassword($hasher->hashPassword($user, $password));
            $user->setResetPasswordToken(null);
            $user->setResetPasswordTokenExpiresAt(null);
            $em->flush();

            $this->addFlash('success', 'Your password has been reset. You can now sign in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/auth/reset-password.html.twig', ['token' => $token]);
    }
}
