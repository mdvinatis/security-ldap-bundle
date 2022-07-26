<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\User;

use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
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
            $ex =  new AccountExpiredException('Your account has deleted');
            $ex->setUser($user);
            throw $ex;
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        if (!$user instanceof UserLdapInterface) {
            return;
        }

        if ($user->isExpired()) {
            $ex =  new AccountExpiredException('Your account has expired');
            $ex->setUser($user);
            throw $ex;
        }

        if (!in_array($this->appAccessApplication, $user->getRoles())) {
            $ex = new CredentialsExpiredException(sprintf('You do not have rights to access the application. ROLE %s required', $this->appAccessApplication));
            $ex->setUser($user);
            throw $ex;
        }
    }
}