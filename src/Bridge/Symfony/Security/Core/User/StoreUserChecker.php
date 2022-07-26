<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\User;

use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class StoreUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if ($user->isDeleted()) {
            $ex =  new AccountExpiredException('Your account has deleted');
            $ex->setUser($user);
            throw $ex;
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        if ($user->isExpired()) {
            $ex =  new AccountExpiredException('Your account has expired');
            $ex->setUser($user);
            throw $ex;
        }
    }
}