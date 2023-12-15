<?php

namespace SRIO\RestUploadBundle\Tests\Upload;

class SimpleUploadTest extends AbstractUploadTestCase
{
    public function testWithEmptyContentTypeHeaderSimpleUpload()
    {
        $client = $this->getNewClient();
        $queryParameters = ['uploadType' => 'simple', 'name' => 'test'];

        $content = $this->getResource($client, 'apple.gif');

        // Set empty content type header since Request defaults to application/x-www-form-urlencoded
        $client->request('POST', '/upload?'.http_build_query($queryParameters), [], [], ['CONTENT_TYPE' => ''], $content);

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithoutFormSimpleUpload()
    {
        $client = $this->getNewClient();
        $queryParameters = ['uploadType' => 'simple'];
        $content = $this->getResource($client, 'apple.gif');
        $client->request('POST', '/upload?'.http_build_query($queryParameters), [], [], ['CONTENT_TYPE' => 'image/gif', 'CONTENT_LENGTH' => strlen($content)]);

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithoutContentSimpleUpload()
    {
        $client = $this->getNewClient();
        $queryParameters = ['uploadType' => 'simple', 'name' => 'test'];
        $client->request('POST', '/upload?'.http_build_query($queryParameters));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testSimpleUpload()
    {
        $client = $this->getNewClient();
        $queryParameters = ['uploadType' => 'simple', 'name' => 'test'];

        $content = $this->getResource($client, 'apple.gif');
        $client->request('POST', '/upload?'.http_build_query($queryParameters), [], [], ['CONTENT_TYPE' => 'image/gif', 'CONTENT_LENGTH' => strlen($content)], $content);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $jsonContent = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($jsonContent);
        $this->assertTrue(array_key_exists('path', $jsonContent));
        $this->assertTrue(array_key_exists('size', $jsonContent));
        $this->assertTrue(array_key_exists('name', $jsonContent));
        $this->assertEquals('test', $jsonContent['name']);
        $this->assertEquals(strlen($content), $jsonContent['size']);

        $localPath = $this->getUploadedFilePath($client).$jsonContent['path'];
        $this->assertTrue(file_exists($localPath));
        $this->assertEquals($content, file_get_contents($localPath));
        $this->assertTrue(array_key_exists('id', $jsonContent));
        $this->assertNotEmpty($jsonContent['id']);
    }
}
