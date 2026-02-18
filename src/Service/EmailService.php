<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail = 'noreply@rlife.com',
        private string $fromName = 'RLIFE Team'
    ) {}

    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('🎉 Welcome to RLIFE - Verify Your Account!')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $user->getVerificationTokenExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(User $user, string $resetUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Reset your RLIFE password')
            ->htmlTemplate('emails/password-reset.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresAt' => $user->getResetPasswordTokenExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('✨ Your RLIFE Journey Begins!')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
