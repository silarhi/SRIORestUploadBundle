<?php

namespace SRIO\RestUploadBundle\DependencyInjection\Factory;

use SRIO\RestUploadBundle\Storage\FileStorage;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class StorageFactory
{
    /**
     * Create the storage service.
     */
    public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $adapterId = $config['filesystem'].'.adapter';

        if ('gaufrette' === $config['type']) {
            $adapterDefinition = new ChildDefinition('srio_rest_upload.storage.gaufrette_adapter');
            $adapterDefinition->setPublic(false);
            $adapterDefinition->replaceArgument(0, new Reference($config['filesystem']));

            $container->setDefinition($adapterId, $adapterDefinition);
        } elseif ('flysystem' === $config['type']) {
            $adapterDefinition = new ChildDefinition('srio_rest_upload.storage.flysystem_adapter');
            $adapterDefinition->setPublic(false);
            $adapterDefinition->replaceArgument(0, new Reference($config['filesystem']));

            $container->setDefinition($adapterId, $adapterDefinition);
        }

        $container
            ->setDefinition($id, new Definition(FileStorage::class))
            ->addArgument($config['name'])
            ->addArgument(new Reference($adapterId))
            ->addArgument(new Reference($config['storage_strategy']))
            ->addArgument(new Reference($config['naming_strategy']))
        ;
    }
}
