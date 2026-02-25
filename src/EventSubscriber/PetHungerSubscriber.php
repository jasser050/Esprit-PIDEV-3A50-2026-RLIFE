<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\PetHungerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

class PetHungerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly PetHungerService $petHungerService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $pet = $user->getMainPet();
        if (!$pet) {
            return;
        }

        if ($this->petHungerService->syncPetHunger($pet)) {
            $this->entityManager->flush();
        }
    }
}

