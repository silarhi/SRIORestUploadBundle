<?php

namespace SRIO\RestUploadBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use SRIO\RestUploadBundle\DependencyInjection\Factory\StorageFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SRIORestUploadExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('srio_rest_upload.upload_type_parameter', $config['upload_type_parameter']);
        $container->setParameter('srio_rest_upload.resumable_entity_class', $config['resumable_entity_class']);
        $container->setParameter('srio_rest_upload.default_storage', $config['default_storage']);

        $this->createStorageVoter($container, $config['storage_voter']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('processors.xml');
        $loader->load('handlers.xml');
        $loader->load('strategy.xml');
        $loader->load('storage.xml');

        $this->createStorageServices($container, $config['storages']);
    }

    /**
     * Create storage services.
     */
    private function createStorageServices(ContainerBuilder $container, array $storageDefinitions): void
    {
        $voterDefinition = $container->getDefinition('srio_rest_upload.storage_voter');
        $factory = new StorageFactory();

        foreach ($storageDefinitions as $name => $storage) {
            $id = $this->createStorage($factory, $container, $name, $storage);
            $voterDefinition->addMethodCall('addStorage', [new Reference($id)]);
        }
    }

    /**
     * Create a single storage service.
     */
    private function createStorage(StorageFactory $factory, ContainerBuilder $containerBuilder, $name, array $config): string
    {
        $id = sprintf('srio_rest_upload.storage.%s', $name);

        $config['name'] = $name;
        $factory->create($containerBuilder, $id, $config);

        return $id;
    }

    /**
     * Create the storage voter.
     */
    private function createStorageVoter(ContainerBuilder $builder, $service): void
    {
        $definition = new ChildDefinition($service);
        $builder->setDefinition('srio_rest_upload.storage_voter', $definition);
    }
}
