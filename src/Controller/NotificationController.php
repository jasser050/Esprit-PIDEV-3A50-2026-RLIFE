<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    private NotificationRepository $notificationRepo;
    private EntityManagerInterface $em;

    public function __construct(
        NotificationRepository $notificationRepo,
        EntityManagerInterface $em
    ) {
        $this->notificationRepo = $notificationRepo;
        $this->em = $em;
    }

    /**
     * Page qui affiche la liste complete des notifications de l'utilisateur
     */
    #[Route('', name: 'app_notifications_list')]
    public function listNotifications(): Response
    {
        $userId = $this->getAuthenticatedUserId();

        $notifications = $this->notificationRepo->createQueryBuilder('n')
            ->andWhere('IDENTITY(n.user) = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('notification/list.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Marquer une notification comme lue (appelee en AJAX quand on clique dessus)
     */
    #[Route('/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markRead(Notification $notification): JsonResponse
    {
        if ($notification->getUser()?->getId() !== $this->getAuthenticatedUserId()) {
            throw $this->createAccessDeniedException();
        }

        $notification->setIsRead(true);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * (Optionnel) Marquer TOUTES les notifications comme lues
     */
    #[Route('/mark-all-read', name: 'app_notification_mark_all_read', methods: ['POST'])]
    public function markAllRead(): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();

        $notifications = $this->notificationRepo->createQueryBuilder('n')
            ->andWhere('IDENTITY(n.user) = :userId')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('userId', $userId)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getResult();

        foreach ($notifications as $notif) {
            $notif->setIsRead(true);
        }

        $this->em->flush();

        return $this->json(['success' => true]);
    }

    private function getAuthenticatedUserId(): int
    {
        $user = $this->getUser();

        if (!is_object($user) || !method_exists($user, 'getId')) {
            throw $this->createAccessDeniedException();
        }

        $userId = $user->getId();

        if (!is_int($userId)) {
            throw $this->createAccessDeniedException();
        }

        return $userId;
    }
}
