<?php
// src/Service/NotificationManager.php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationManager
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function createNotification(
        User $user,
        string $title,
        string $message,
        string $type,
        ?string $link = null
    ): void {
        $notif = new Notification();
        $notif->setUser($user);
        $notif->setTitle($title);
        $notif->setMessage($message);
        $notif->setType($type);
        $notif->setLink($link);

        $this->em->persist($notif);
        $this->em->flush();
    }
}