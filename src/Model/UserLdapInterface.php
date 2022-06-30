<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Model;

use Symfony\Component\Security\Core\User\UserInterface;
use DateTime;
use Symfony\Component\Uid\Uuid;

interface UserLdapInterface extends UserInterface
{
    public function getId(): Uuid;
    public function getEmail(): string;
    public function isDeleted(): bool;
    public function isExpired(): bool;
    public function setExpiration(DateTime $expiration): self;
    public function setFirstName(string $firstName): self;
    public function getFirstName(): ?string;
    public function setLastName(string $lastName): self;
    public function getLastName(): ?string;
    public function setPassword(string $password): self;
    public function setRoles(array $roles): self;
}