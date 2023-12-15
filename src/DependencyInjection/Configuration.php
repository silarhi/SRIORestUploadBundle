<?php

namespace SRIO\RestUploadBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('srio_rest_upload');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('storages')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->enumNode('type')
                                ->values(['gaufrette', 'flysystem'])
                                ->defaultValue('gaufrette')
                            ->end()
                            ->scalarNode('filesystem')->isRequired()->end()
                            ->scalarNode('naming_strategy')
                                ->defaultValue('srio_rest_upload.naming.default_strategy')
                            ->end()
                            ->scalarNode('storage_strategy')
                                ->defaultValue('srio_rest_upload.storage.default_strategy')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_storage')->defaultNull()->end()
                ->scalarNode('storage_voter')
                    ->cannotBeEmpty()
                    ->defaultValue('srio_rest_upload.storage_voter.default')
                ->end()
                ->scalarNode('resumable_entity_class')->defaultNull()->end()
                ->scalarNode('upload_type_parameter')
                    ->defaultValue('uploadType')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
