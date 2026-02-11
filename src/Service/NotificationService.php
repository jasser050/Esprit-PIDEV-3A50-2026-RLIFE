<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NotificationService
{
    private MailerInterface $mailer;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        MailerInterface $mailer,
        ParameterBagInterface $params
    ) {
        $this->mailer = $mailer;
        $this->fromEmail = $params->get('mailer_from_email');

        $this->fromName  = $params->get('mailer_from_name');

    }

    /**
     * Notification pour échéance d'une tâche (Assignment)
     */
    public function notifyTaskDeadline(Assignment $task): void
    {
        $user = $task->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        $projectTitle = $task->getProject() ? $task->getProject()->getTitre() : null;

        $subject = "Échéance proche : " . $task->getTitre();

        $html = $this->getTaskDeadlineHtml(
            $user->getFullName() ?? $user->getEmail(),
            $task->getTitre(),
            $task->getDateFin()->format('d/m/Y'),
            $projectTitle
        );

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($user->getEmail(), $user->getFullName() ?? ''))
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Notification pour échéance d'un projet
     */
    public function notifyProjectDeadline(Project $project): void
    {
        $user = $project->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        $subject = "Échéance projet proche : " . $project->getTitre();

        $html = $this->getProjectDeadlineHtml(
            $user->getFullName() ?? $user->getEmail(),
            $project->getTitre(),
            $project->getDateFin()->format('d/m/Y')
        );

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($user->getEmail(), $user->getFullName() ?? ''))
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }

    private function getTaskDeadlineHtml(string $userName, string $taskTitle, string $deadline, ?string $projectTitle): string
    {
        return <<<HTML
        <h2>Bonjour {$userName},</h2>
        <p><strong>Attention :</strong> la tâche <strong>{$taskTitle}</strong> arrive à échéance le <strong>{$deadline}</strong>.</p>
        HTML . ($projectTitle ? "<p>Elle fait partie du projet <strong>{$projectTitle}</strong>.</p>" : "") . <<<HTML
        <p>Veuillez vérifier et avancer dès que possible.</p>
        <p><a href="https://votre-app-url/assignments" style="background:#3b82f6;color:white;padding:10px 20px;text-decoration:none;border-radius:6px;">Voir mes tâches</a></p>
        <p>Cordialement,<br>L'équipe RLIFE</p>
        HTML;
    }

    private function getProjectDeadlineHtml(string $userName, string $projectTitle, string $deadline): string
    {
        return <<<HTML
        <h2>Bonjour {$userName},</h2>
        <p><strong>Attention :</strong> le projet <strong>{$projectTitle}</strong> arrive à échéance le <strong>{$deadline}</strong>.</p>
        <p>C'est le moment de finaliser les derniers points !</p>
        <p><a href="https://votre-app-url/projects" style="background:#3b82f6;color:white;padding:10px 20px;text-decoration:none;border-radius:6px;">Voir mes projets</a></p>
        <p>Cordialement,<br>L'équipe RLIFE</p>
        HTML;
    }
}