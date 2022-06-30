<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Encoder;

final class ShaEncoderStrategy implements EncoderStrategyInterface
{
    public function encode(string $plainPassword): string
    {
        return "{SHA}" . base64_encode( pack( "H*", sha1( $plainPassword ) ) );
    }
}