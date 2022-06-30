<?php

namespace Vinatis\Bundle\SecurityLdapBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Vinatis\Bundle\SecurityLdapBundle\Service\ActiveDirectory;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;

/**
 * Class VinatisSecurityLdapExtension.
 */
final class VinatisSecurityLdapExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function getAlias(): string
    {
        return 'vinatis_security_ldab';
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('commands.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container
            ->register(ActiveDirectory::class, ActiveDirectory::class)
            ->setArguments([
                $config['service']['dn'],
                $config['service']['user'],
                $config['service']['password']
            ])
        ;

        $container
            ->register(Adapter::class, Adapter::class)
            ->setArguments([
                $config['service']['host'],
                $config['service']['port'],
                $config['service']['options']
            ])
        ;

        $container
            ->register(Ldap::class, Ldap::class)
            ->addTag('ldap');
        ;
    }
}
