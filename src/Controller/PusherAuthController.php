<?php

namespace App\Controller;

use App\Service\PusherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/pusher')]
class PusherAuthController extends AbstractController
{
    #[Route('/auth', name: 'pusher_auth', methods: ['POST'])]
    public function auth(Request $request, PusherService $pusherService): JsonResponse
    {
        $socketId = $request->request->get('socket_id');
        $channelName = $request->request->get('channel_name');

        if (!$socketId || !$channelName || !str_starts_with($channelName, 'private-user-')) {
            throw $this->createAccessDeniedException();
        }

        $userChannelId = str_replace('private-user-', '', $channelName);
        if ((string)$this->getUser()->getId() !== $userChannelId) {
            throw $this->createAccessDeniedException();
        }

        $pusher = $pusherService->getPusher();
        $auth = $pusher->socket_auth($channelName, $socketId);

        return new JsonResponse($auth);
    }
}