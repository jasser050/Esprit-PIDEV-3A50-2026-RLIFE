<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class FormLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private RouterInterface $router,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Get the user trying to login
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        // Check if user email is verified
        if ($user && method_exists($user, 'isVerified') && !$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Please verify your email address before logging in. Check your inbox for the verification link.'
            );
        }
        
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        // Store the logged-in user ID in the session
        $session = $this->requestStack->getSession();
        if (method_exists($user, 'getId')) {
            $session->set('logged_in_user_id', $user->getId());
        }

        // Check if user is banned
        if (method_exists($user, 'isBanned') && $user->isBanned()) {
            return new RedirectResponse($this->router->generate('app_logout'));
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            if ($this->isBrowserPageTargetPath($targetPath)) {
                return new RedirectResponse($targetPath);
            }

            // Prevent post-login redirects to JSON/AJAX endpoints.
            $this->removeTargetPath($request->getSession(), $firewallName);
        }

        // Redirect based on role
        $roles = $token->getRoleNames();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }

    private function isBrowserPageTargetPath(string $targetPath): bool
    {
        $path = (string) (parse_url($targetPath, PHP_URL_PATH) ?? '');
        if ($path === '') {
            return false;
        }

        $blockedPrefixes = [
            '/pet/metaverse/communications',
            '/pet/metaverse/communicate',
            '/pet/metaverse/reward',
            '/pet/status',
            '/pet/action',
            '/pet/leaderboard',
            '/pet/opponents',
            '/pet/battle',
            '/pet/feed',
            '/pet/rename',
            '/api/',
            '/_wdt/',
            '/_profiler/',
        ];

        foreach ($blockedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
