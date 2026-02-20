<?php

namespace App\Security;

use App\Entity\User;
use App\Service\AdminMailerService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private AdminMailerService $adminMailerService;
    private RequestStack $requestStack;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        AdminMailerService $adminMailerService,
        RequestStack $requestStack
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->adminMailerService = $adminMailerService;
        $this->requestStack = $requestStack;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                // Check if another user is already logged in on this device
                $session = $this->requestStack->getSession();
                $currentLoggedInUserId = $session->get('logged_in_user_id');

                // Check if user exists by Google ID
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['googleId' => $googleId]);

                if ($existingUser) {
                    // If a different user is trying to login, block it
                    if ($currentLoggedInUserId && method_exists($existingUser, 'getId') && $existingUser->getId() !== $currentLoggedInUserId) {
                        throw new CustomUserMessageAuthenticationException(
                            'Another user is already logged in on this device. Please log out the current user first before logging in with a different account.'
                        );
                    }
                    return $existingUser;
                }

                // Check if user exists by email
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    // If a different user is trying to login, block it
                    if ($currentLoggedInUserId && method_exists($existingUser, 'getId') && $existingUser->getId() !== $currentLoggedInUserId) {
                        throw new CustomUserMessageAuthenticationException(
                            'Another user is already logged in on this device. Please log out the current user first before logging in with a different account.'
                        );
                    }
                    
                    // Link Google ID to existing account
                    $existingUser->setGoogleId($googleId);
                    $this->entityManager->flush();
                    return $existingUser;
                }

                // Check if a different user is already logged in before creating new account
                if ($currentLoggedInUserId) {
                    throw new CustomUserMessageAuthenticationException(
                        'Another user is already logged in on this device. Please log out the current user first before logging in with a different account.'
                    );
                }

                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setGoogleId($googleId);
                
                // Extract name from Google
                $firstName = $googleUser->getFirstName() ?? 'User';
                $lastName = $googleUser->getLastName() ?? '';
                $user->setFirstName($firstName);
                $user->setLastName($lastName);

                // Generate username from email
                $username = explode('@', $email)[0];
                $baseUsername = $username;
                $counter = 1;
                
                // Ensure unique username
                while ($this->entityManager->getRepository(User::class)->findOneBy(['username' => $username])) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                $user->setUsername($username);

                // Set default password (they can change it later if they want)
                $user->setPassword(password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT));
                
                // Set default values
                $user->setRoles(['ROLE_USER']);
                $user->setGender('other'); // Default gender
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());

                // Get profile picture from Google
                if ($googleUser->getAvatar()) {
                    $user->setProfilePic($googleUser->getAvatar());
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Send welcome email
                try {
                    $this->adminMailerService->sendWelcomeEmail($user);
                } catch (\Exception $e) {
                    // Log error but don't fail authentication
                }

                return $user;
            })
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

        // Check if user has admin role
        $roles = $token->getRoleNames();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new RedirectResponse(
            $this->router->generate('app_login', ['error' => 'google_auth_failed'])
        );
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
