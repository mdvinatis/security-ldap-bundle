<?php

namespace Vinatis\Bundle\SecurityLdapBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Vinatis\Bundle\SecurityLdapBundle\Encoder\EncoderStrategyInterface;
use Vinatis\Bundle\SecurityLdapBundle\Encoder\ShaEncoderStrategy;
use Vinatis\Bundle\SecurityLdapBundle\Service\ActiveDirectory;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\User\UserChecker;
use Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Core\Encoder\PrestashopLegacyEncoder;

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
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('vinatis_security_ldab.entity.class', $config['entity']['class']);

        $container
            ->register(UserChecker::class, UserChecker::class)
            ->setArguments([$config['access']['role']])
        ;

        $container
            ->register(Adapter::class, Adapter::class)
            ->setArguments([
                [
                    'host' => $config['service']['host'],
                    'port' => $config['service']['port'],
                    'options' => $config['service']['options']
                ]
            ])
        ;

        $container
            ->register(Ldap::class, Ldap::class)
            ->setArguments([$container->getDefinition(Adapter::class)])
            ->addTag('ldap')
        ;

        $container->register(EncoderStrategyInterface::class, ShaEncoderStrategy::class);

        $container
            ->register(ActiveDirectory::class, ActiveDirectory::class)
            ->setArguments([
                $container->getDefinition(Adapter::class),
                $container->getDefinition(EncoderStrategyInterface::class),
                $config['service']['dn'],
                $config['service']['user'],
                $config['service']['password']
            ])
        ;

        $container
            ->register(PrestashopLegacyEncoder::class, PrestashopLegacyEncoder::class)
            ->setArguments([
                $config['legacy']['cookie_key']
            ])
        ;
    }
}
