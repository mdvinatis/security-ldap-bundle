<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\User;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vinatis\Bundle\SecurityLdapBundle\Model\UserLdapInterface;

final class UserChecker implements UserCheckerInterface
{
    private string $appAccessApplication;

    public function __construct(string $appAccessApplication)
    {
        $this->appAccessApplication = $appAccessApplication;
    }

    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof UserLdapInterface) {
            return;
        }

        if ($user->isDeleted()) {
            throw new AccountExpiredException('Your account has deleted');
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        if (!$user instanceof UserLdapInterface) {
            return;
        }

        if ($user->isExpired()) {
            throw new AccountExpiredException('Your account has expired');
        }

        if (!in_array($this->appAccessApplication, $user->getRoles())) {
            throw new UserNotFoundException($user->getEmail(),
                sprintf('You do not have rights to access the application. ROLE %s required', $this->appAccessApplication)
            );
        }
    }
}