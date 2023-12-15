<?php

namespace SRIO\RestUploadBundle\Tests\Upload;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractUploadTestCase extends WebTestCase
{
    /**
     * Assert that response has errors.
     */
    protected function assertResponseHasErrors(KernelBrowser $client): void
    {
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Get content of a resource.
     *
     * @throws RuntimeException
     */
    protected function getResource(KernelBrowser $client, string $name): string
    {
        $filePath = $this->getResourcePath($client, $name);
        if (!file_exists($filePath)) {
            throw new RuntimeException(sprintf('File %s do not exists', $filePath));
        }

        return file_get_contents($filePath);
    }

    /**
     * Get uploaded file path.
     */
    protected function getUploadedFilePath(KernelBrowser $client): string
    {
        return $client->getKernel()->getProjectDir().'/tests/Fixtures/web/uploads';
    }

    /**
     * Get resource path.
     */
    protected function getResourcePath(KernelBrowser $client, string $name): string
    {
        return $client->getKernel()->getProjectDir().'/tests/Fixtures/Resources/'.$name;
    }

    /**
     * Creates a Client.
     *
     * @param array $options An array of options to pass to the createKernel class
     * @param array $server  An array of server parameters
     */
    protected function getNewClient(array $options = [], array $server = []): KernelBrowser
    {
        $options['environment'] = $_SERVER['TEST_FILESYSTEM'] ?? 'gaufrette';

        return static::createClient($options, $server);
    }
}
