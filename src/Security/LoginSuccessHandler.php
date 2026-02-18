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

      
        if (method_exists($user, 'isBanned') && $user->isBanned()) {
           
            return new RedirectResponse($this->router->generate('app_logout'));
        }

        
        $roles = $token->getRoleNames();
        if (in_array('ROLE_ADMIN', $roles, true)) {
           
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

       
        return new RedirectResponse($this->router->generate('app_dashboard'));
    }
}
