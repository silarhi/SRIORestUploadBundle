<?php

namespace SRIO\RestUploadBundle\Tests\Upload;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ResumableUploadTest extends AbstractUploadTestCase
{
    public function testCompleteUpload()
    {
        $client = $this->startSession();

        $response = $client->getResponse();
        $location = $response->headers->get('Location');
        $content = $this->getResource($client, 'apple.gif');
        $client->request('PUT', $location, [], [], ['CONTENT_TYPE' => 'image/gif', 'CONTENT_LENGTH' => strlen($content)], $content);

        $this->assertSuccessful($client, $content);
    }

    public function testChunkedUpload()
    {
        $client = $this->startSession();

        $response = $client->getResponse();
        $location = $response->headers->get('Location');
        $content = $this->getResource($client, 'apple.gif');
        $chunkSize = 256;

        for ($start = 0; $start < strlen($content); $start += $chunkSize) {
            $part = substr($content, $start, $chunkSize);
            $end = $start + strlen($part) - 1;
            $client->request('PUT', $location, [], [], ['CONTENT_TYPE' => 'image/gif', 'CONTENT_LENGTH' => strlen($part), 'HTTP_CONTENT_RANGE' => 'bytes '.$start.'-'.$end.'/'.strlen($content)], $part);

            $response = $client->getResponse();
            if (($start + $chunkSize) < strlen($content)) {
                $this->assertEquals(308, $response->getStatusCode());
                $this->assertEquals('0-'.$end, $response->headers->get('Range'));

                $client->request('PUT', $location, [], [], ['CONTENT_LENGTH' => 0, 'HTTP_CONTENT_RANGE' => 'bytes */'.strlen($content)]);

                $response = $client->getResponse();
                $this->assertEquals(308, $response->getStatusCode());
                $this->assertEquals('0-'.$end, $response->headers->get('Range'));
            }
        }

        $this->assertSuccessful($client, $content);
    }

    protected function startSession(): KernelBrowser
    {
        $client = $this->getNewClient();
        $content = $this->getResource($client, 'apple.gif');
        $parameters = ['name' => 'test'];
        $json = json_encode($parameters);

        $client->request('POST', '/upload?uploadType=resumable', [], [], ['CONTENT_TYPE' => 'application/json', 'CONTENT_LENGTH' => strlen($json), 'HTTP_X-Upload-Content-Type' => 'image/gif', 'HTTP_X-Upload-Content-Length' => strlen($content)], $json);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Location'));
        $this->assertEquals(0, $response->headers->get('Content-Length', 0));

        return $client;
    }

    protected function assertSuccessful(KernelBrowser $client, $content): void
    {
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $jsonContent = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($jsonContent);
        $this->assertTrue(array_key_exists('path', $jsonContent));
        $this->assertTrue(array_key_exists('size', $jsonContent));
        $this->assertTrue(array_key_exists('name', $jsonContent));
        $this->assertEquals('test', $jsonContent['name']);
        $this->assertEquals(strlen((string) $content), $jsonContent['size']);

        $filePath = $this->getUploadedFilePath($client).$jsonContent['path'];
        $this->assertTrue(file_exists($filePath));
        $this->assertEquals($content, file_get_contents($filePath));
        $this->assertTrue(array_key_exists('id', $jsonContent));
        $this->assertNotEmpty($jsonContent['id']);
    }
}
