<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\Encoder;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

final class PrestashopLegacyEncoder implements PasswordEncoderInterface
{
    private string $prestashopCookieKey;

    public function __construct(string $prestashopCookieKey)
    {
        $this->prestashopCookieKey = $prestashopCookieKey;
    }

    /**
     * {@inheritDoc}
     */
    public function encodePassword(string $raw, ?string $salt): string
    {
        return md5($this->prestashopCookieKey . $raw);
    }

    /**
     * {@inheritDoc}
     */
    public function isPasswordValid(string $encoded, string $raw, ?string $salt): bool
    {
        return $encoded === $this->encodePassword($raw, $salt);
    }

    /**
     * {@inheritDoc}
     */
    public function needsRehash(string $encoded): bool
    {
        if (!str_starts_with($encoded, '$argon')) {
            return true;
        }

        return false;
    }
}
