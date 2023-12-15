<?php

namespace SRIO\RestUploadBundle\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\GaufretteBundle\KnpGaufretteBundle;
use Oneup\FlysystemBundle\OneupFlysystemBundle;
use Psr\Log\NullLogger;
use SRIO\RestUploadBundle\SRIORestUploadBundle;
use SRIO\RestUploadBundle\Tests\Fixtures\Entity\ResumableUploadSession;
use SRIO\RestUploadBundle\Upload\UploadHandler;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        if ('gaufrette' === $this->environment) {
            yield new KnpGaufretteBundle();
        }

        if ('flysystem' === $this->environment) {
            yield new OneupFlysystemBundle();
        }

        yield new SRIORestUploadBundle();
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', [
            'secret' => 'S3CRET',
            'http_method_override' => false,
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        ]);

        $c->extension('doctrine', [
            'dbal' => ['url' => '%env(resolve:DATABASE_URL)%'],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'RestUploadBundleTest' => [
                        'type' => 'annotation',
                        'is_bundle' => false,
                        'dir' => '%kernel.project_dir%/tests/Fixtures/Entity',
                        'prefix' => 'SRIO\RestUploadBundle\Tests\Fixtures\Entity',
                        'alias' => 'RestUploadBundleTest',
                    ],
                    'SRIORestUploadBundle' => [
                        'type' => 'xml',
                        'is_bundle' => true,
                    ],
                ],
            ],
        ]);

        $c->extension('srio_rest_upload', [
            'upload_type_parameter' => 'uploadType',
            'resumable_entity_class' => ResumableUploadSession::class,
        ]);

        if ('gaufrette' === $this->environment) {
            $c->extension('knp_gaufrette', [
                'adapters' => [
                    'test' => [
                        'local' => [
                            'directory' => '%kernel.project_dir%/tests/Fixtures/web/uploads',
                        ],
                    ],
                ],
                'filesystems' => [
                    'test' => [
                        'adapter' => 'test',
                    ],
                ],
            ]);

            $c->extension('srio_rest_upload', [
                'storages' => [
                    'default' => [
                        'type' => 'gaufrette',
                        'filesystem' => 'gaufrette.test_filesystem',
                    ],
                ],
            ]);
        }

        if ('flysystem' === $this->environment) {
            $c->extension('oneup_flysystem', [
                'adapters' => [
                    'test' => [
                        'local' => [
                            'location' => '%kernel.project_dir%/tests/Fixtures/web/uploads',
                        ],
                    ],
                ],
                'filesystems' => [
                    'test' => [
                        'adapter' => 'test',
                    ],
                ],
            ]);

            $c->extension('srio_rest_upload', [
                'storages' => [
                    'default' => [
                        'type' => 'flysystem',
                        'filesystem' => 'oneup_flysystem.test_filesystem',
                    ],
                ],
            ]);
        }

        $services = $c->services();
        $services
            ->defaults()
            ->autowire()
            ->autoconfigure()
            // disable logging errors to the console
            ->set('logger', NullLogger::class)
            ->load(__NAMESPACE__.'\\', __DIR__)
            ->exclude(['Kernel.php']);

        $services->alias(UploadHandler::class, 'srio_rest_upload.upload_handler');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/Controller/', 'annotation');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/SRIORestUploadBundle/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/SRIORestUploadBundle/logs';
    }
}
