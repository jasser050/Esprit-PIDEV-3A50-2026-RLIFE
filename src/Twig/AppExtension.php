<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private NotificationRepository $notificationRepository;
    private Security $security;

    public function __construct(NotificationRepository $notificationRepository, Security $security)
    {
        $this->notificationRepository = $notificationRepository;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_notifications', [$this, 'getNotifications']),
        ];
    }

    public function getNotifications(): array
    {
        $user = $this->security->getUser();
        if (!$user) return [];

        return $this->notificationRepository->findBy(
            ['user' => $user, 'isRead' => false],
            ['createdAt' => 'DESC'],
            5
        );
    }
}
