<?php

namespace Vinatis\Bundle\SecurityLdapBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vinatis\Bundle\SecurityLdapBundle\DependencyInjection\VinatisSecurityLdapExtension;

/**
 * Class VinatisSecutiryLdapBundle.
 *
 * @author Michel Dourneau <mdourneau@vinatis.com>
 */
class VinatisSecurityLdapBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new VinatisSecurityLdapExtension();
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {

    }
}