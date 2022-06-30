<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Manager;

use Symfony\Component\Ldap\Entry;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Uid\Uuid;
use Vinatis\Bundle\SecurityLdapBundle\Model\UserLdapInterface;
use Vinatis\Bundle\SecurityLdapBundle\Service\ActiveDirectory;

final class UserLdapManager
{
    private ActiveDirectory $activeDirectory;
    private UserPasswordEncoderInterface $passwordEncoder;
    private string $userClass;

    public function __construct(
        ActiveDirectory $activeDirectory,
        UserPasswordEncoderInterface $passwordEncoder,
        string $userClass
    )
    {
        $this->activeDirectory = $activeDirectory;
        $this->passwordEncoder = $passwordEncoder;
        $this->userClass = $userClass;
    }

    public function getUserClass(): string
    {
        return $this->userClass;
    }

    public function create(Entry $ldapEntry, string $plainPassword): UserLdapInterface
    {
        $attributes = $this->activeDirectory->getAttributes(
            $ldapEntry->getAttribute('cn')[0]
        );

        $class = $this->userClass;
        $entity = new $class(Uuid::fromString($attributes->getAttribute('entryUUID')[0]));
        $entity->setEmail($ldapEntry->getAttribute('cn')[0]);

        return $this->hydrateData($ldapEntry, $entity, $plainPassword);
    }

    public function update(Entry $ldapEntry, UserLdapInterface $entity, string $plainPassword): UserLdapInterface
    {
        return $this->hydrateData($ldapEntry, $entity, $plainPassword);
    }

    private function hydrateData(Entry $ldapEntry, UserLdapInterface $entity, string $plainPassword): UserLdapInterface
    {
        $roles = $this->activeDirectory->getRoles(
            $ldapEntry->getAttribute('uid')[0],
            $ldapEntry->getAttribute('gidNumber')[0]
        );

        if ($ldapEntry->hasAttribute('shadowExpire')) {
            $entity->setExpiration(new \DateTime(sprintf('@%s',
                    (int)$ldapEntry->getAttribute('shadowExpire')[0] * 86400)
            ));
        } else {
            $entity->setExpiration(null);
        }

        $entity->setFirstName($ldapEntry->getAttribute('givenName')[0]);
        $entity->setLastName($ldapEntry->getAttribute('sn')[0]);
        $entity->setPassword($this->passwordEncoder->encodePassword($entity, $plainPassword));
        $entity->setRoles($roles);

        return $entity;
    }
}