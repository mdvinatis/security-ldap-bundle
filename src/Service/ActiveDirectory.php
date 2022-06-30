<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Service;

use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Vinatis\Bundle\SecurityLdapBundle\Encoder\EncoderStrategyInterface;

final class ActiveDirectory
{
    private Ldap $ldap;
    private EncoderStrategyInterface $activeDirectoryEncoder;
    private Adapter $ldapAdapter;
    private string $ldapServiceDn;
    private string $ldapServiceUser;
    private string $ldapServicePassword;

    public function __construct(
        Adapter $ldapAdapter,
        EncoderStrategyInterface $activeDirectoryEncoder,
        string $ldapServiceDn,
        string $ldapServiceUser,
        string $ldapServicePassword
    ) {
        $this->ldapAdapter = $ldapAdapter;
        $this->activeDirectoryEncoder = $activeDirectoryEncoder;
        $this->ldapServiceDn = $ldapServiceDn;
        $this->ldapServiceUser = $ldapServiceUser;
        $this->ldapServicePassword = $ldapServicePassword;

        $this->ldap = new Ldap($this->ldapAdapter);
    }

    public function getUser(string $username, string $password): ?Entry
    {
        $search = false;
        $value = null;

        $bind = sprintf('cn=%s,%s', $username, $this->ldapServiceDn);
        $this->ldap->bind($bind, $password);
        if ($this->ldapAdapter->getConnection()->isBound()) {
            $search = $this->ldap->query(
                sprintf('cn=%s,%s', $username, $this->ldapServiceDn),
                '(objectClass=*)'
            )->execute()->toArray();
        }

        if ($search && 1 === count($search)) {
            $value = $search[0];
        }

        return $value;
    }

    public function getAttributes(string $cn): ?Entry
    {
        $search = false;
        $value = null;

        $this->ldap->bind(implode(',', [$this->ldapServiceUser, $this->ldapServiceDn]), $this->ldapServicePassword);
        if ($this->ldapAdapter->getConnection()->isBound()) {
            $search = $this->ldap->query(
                $this->ldapServiceDn,
                sprintf('(cn=%s)', $cn),
                ['filter' => ['+', 'objectClass', 'creatorsName', 'createTimestamp', 'modifiersName', 'modifyTimestamp', 'hasSubordinates', 'pwdChangedTime']]
            )->execute()->toArray();
        }

        if ($search && 1 === count($search)) {
            $value = $search[0];
        }

        return $value;
    }

    public function getRoles(string $uid, string $gidNumber): array
    {
        $roles = [];

        $allRoles = $this->getGroups($uid);
        if (null !== $allRoles){
            foreach ($allRoles as $allRole) {
                $roles[$allRole->getAttribute('cn')[0]] = $allRole->getAttribute('cn')[0];
            }
        }

        $defaultRoles = $this->getDefaultGroups($gidNumber);
        if (null !== $defaultRoles){
            $roles[$defaultRoles->getAttribute('cn')[0]] = $defaultRoles->getAttribute('cn')[0];
        }

        return $roles;
    }

    public function getGroups(string $uid): ?array
    {
        $values = null;
        try {
            $this->ldap->bind(implode(',', [$this->ldapServiceUser, $this->ldapServiceDn]), $this->ldapServicePassword);
            $search = $this->ldap->query(
                $this->ldapServiceDn,
                sprintf('(&(objectclass=posixGroup)(memberUid=%s))', $uid)
            )->execute()->toArray();
        } catch (ConnectionException) {
            return null;
        }
        if ($search && count($search) > 0) {
            $values = $search;
        }

        return $values;
    }

    public function getDefaultGroups(string $gidNumber): ?Entry
    {
        $value = null;
        try {
            $this->ldap->bind(implode(',', [$this->ldapServiceUser, $this->ldapServiceDn]), $this->ldapServicePassword);
            $search = $this->ldap->query(
                $this->ldapServiceDn,
                sprintf('(&(gidNumber=%s)(objectClass=posixGroup))', $gidNumber)
            )->execute()->toArray();
        } catch (ConnectionException) {
            return null;
        }
        if ($search && 1 === count($search)) {
            $value = $search[0];
        }
        return $value;
    }

    public function updatePassword(string $cn, string $plainPassword): void
    {
        try {
            $this->ldap->bind(implode(',', [$this->ldapServiceUser, $this->ldapServiceDn]), $this->ldapServicePassword);
            $entries = $this->ldap->query(
                $this->ldapServiceDn,
                sprintf('(cn=%s)', $cn),
            )->execute()->toArray();
        } catch (ConnectionException $e) {
            throw new \RuntimeException($e->getMessage());
        }
        if ($entries && 1 === count($entries)) {
            $entry = $entries[0];
            $entry->setAttribute('userPassword', [$this->activeDirectoryEncoder->encode($plainPassword)]);
            $this->ldap->getEntryManager()->update($entry);
        } else {
            throw new \RuntimeException('Unable to update password on LDAP');
        }
    }
}