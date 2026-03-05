<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ServerRestartSubscriber implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private UrlGeneratorInterface $urlGenerator;
    private string $projectDir;
    private ?string $pidFile;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $urlGenerator,
        string $projectDir,
        ?string $pidFile = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
        $this->projectDir = $projectDir;
        $this->pidFile = $pidFile ? trim($pidFile) : null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        if (!is_object($user)) {
            return;
        }

        $stamp = $this->getServerStamp();
        if (!$stamp) {
            return;
        }

        $stored = $session->get('_server_pid_stamp');
        if ($stored && $stored !== $stamp) {
            $session->invalidate();
            $this->tokenStorage->setToken(null);

            if ($request->attributes->get('_route') !== 'app_login') {
                $response = new RedirectResponse($this->urlGenerator->generate('app_login'));
                $response->headers->setCookie(Cookie::create('REMEMBERME')->withValue('')->withExpires(1));
                $event->setResponse($response);
            }
            return;
        }

        if (!$stored) {
            $session->set('_server_pid_stamp', $stamp);
        }
    }

    private function getServerStamp(): ?string
    {
        $pidFile = $this->resolvePidFile();
        if (!$pidFile) {
            return null;
        }

        $pid = @file_get_contents($pidFile);
        $mtime = @filemtime($pidFile);
        $pid = $pid !== false ? trim($pid) : '';
        $mtime = $mtime !== false ? (string) $mtime : '';

        if ($pid === '' && $mtime === '') {
            return null;
        }

        return $pid . '@' . $mtime;
    }

    private function resolvePidFile(): ?string
    {
        $candidates = [];
        if ($this->pidFile) {
            $candidates[] = $this->pidFile;
        }
        $candidates[] = 'C:\\xampp\\apache\\logs\\httpd.pid';
        $candidates[] = $this->projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'run' . DIRECTORY_SEPARATOR . 'server.pid';
        $candidates[] = '/var/run/apache2/apache2.pid';
        $candidates[] = '/var/run/httpd/httpd.pid';

        foreach ($candidates as $path) {
            if ($path && @is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
