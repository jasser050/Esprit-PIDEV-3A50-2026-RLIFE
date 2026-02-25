<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

<<<<<<< HEAD
        // Check if user is banned
        if (method_exists($user, 'isBanned') && $user->isBanned()) {
            // Redirect to logout if banned
            return new RedirectResponse($this->router->generate('app_logout'));
        }

        // Check if user has admin role
        $roles = $token->getRoleNames();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            // Redirect to admin dashboard
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        // Default redirect to user dashboard
=======
      
        if (method_exists($user, 'isBanned') && $user->isBanned()) {
           
            return new RedirectResponse($this->router->generate('app_logout'));
        }

        
        $roles = $token->getRoleNames();
        if (in_array('ROLE_ADMIN', $roles, true)) {
           
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

       
>>>>>>> 58c374d892597ea6754943c1c6b23fdbb8e095cd
        return new RedirectResponse($this->router->generate('app_dashboard'));
    }
}
