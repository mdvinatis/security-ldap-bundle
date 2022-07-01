<?php

namespace Vinatis\Bundle\SecurityLdapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 *
 * @author Michel Dourneau <mdourneau@vinatis.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vinatis_security_ldab');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->append($this->getServiceNode())
            ->append($this->getAccessNode())
            ->append($this->getEntityNode())
            ->end();

        return $treeBuilder;
    }

    private function getServiceNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('service');
        $node = $treeBuilder->getRootNode();

        $node
            ->children()
                ->scalarNode('dn')->cannotBeEmpty()->isRequired()->end()
                ->scalarNode('user')->cannotBeEmpty()->isRequired()->end()
                ->scalarNode('password')->cannotBeEmpty()->isRequired()->end()
                ->scalarNode('host')->cannotBeEmpty()->isRequired()->end()
                ->scalarNode('port')->cannotBeEmpty()->isRequired()->end()

                ->arrayNode('options')
                    ->children()
                        ->scalarNode('protocol_version')->defaultValue(3)->end()
                        ->scalarNode('referrals')->defaultValue(false)->end()
                    ->end()
                ->end()

            ->end()
            ;

        return $node;
    }

    private function getEntityNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('entity');
        $node = $treeBuilder->getRootNode();

        $node
            ->children()
                ->scalarNode('class')->cannotBeEmpty()->isRequired()->end()
            ->end()
        ;

        return $node;
    }

    private function getAccessNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('access');
        $node = $treeBuilder->getRootNode();

        $node
            ->children()
            ->scalarNode('role')->cannotBeEmpty()->isRequired()->end()
            ->end()
        ;

        return $node;
    }
}
