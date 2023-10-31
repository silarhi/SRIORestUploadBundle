<?php

namespace SRIO\RestUploadBundle\Tests\Processor;

use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\MockObject\MockObject;
use SRIO\RestUploadBundle\Processor\MultipartUploadProcessor;
use SRIO\RestUploadBundle\Voter\StorageVoter;
use SRIO\RestUploadBundle\Upload\StorageHandler;
use Symfony\Component\HttpFoundation\HeaderBag;

class MultipartUploadProcessorTest extends AbstractProcessorTestCase
{
    public function testGetPartsString()
    {
        $client = $this->getNewClient();
        $image = $this->getResource($client, 'apple.gif');
        $data = ['test' => 'OK'];
        $jsonData = json_encode($data);

        $multipartUploadProcessor = $this->getProcessor();
        $request = $this->createMultipartRequest($jsonData, $image);

        $partOne = $this->callMethod($multipartUploadProcessor, 'getPart', [$request, 1]);
        $this->assertTrue(is_array($partOne));
        [$contentType, $body] = $partOne;

        $this->assertEquals('application/json; charset=UTF-8', $contentType);
        $this->assertEquals($jsonData, $body);

        $partTwo = $this->callMethod($multipartUploadProcessor, 'getPart', [$request, 2]);
        $this->assertTrue(is_array($partTwo));
        [$contentType, $body] = $partTwo;

        $this->assertEquals('image/gif', $contentType);
        $this->assertEquals($image, $body);
    }

    public function testGetPartsResource()
    {
        $client = $this->getNewClient();
        $image = $this->getResource($client, 'apple.gif');
        $data = ['test' => 'OK'];
        $jsonData = json_encode($data);
        $boundary = uniqid();
        $content = $this->createMultipartContent($boundary, $jsonData, $image);

        $tempFile = $this->getResourcePath($client, 'test.tmp');
        file_put_contents($tempFile, $content);
        $resource = fopen($tempFile, 'r');

        $multipartUploadProcessor = $this->getProcessor();
        $request = $this->createMultipartRequestWithContent($boundary, $resource);

        $partOne = $this->callMethod($multipartUploadProcessor, 'getPart', [$request, 1]);
        $this->assertTrue(is_array($partOne));
        [$contentType, $body] = $partOne;

        $this->assertEquals('application/json; charset=UTF-8', $contentType);
        $this->assertEquals($jsonData, $body);

        $partTwo = $this->callMethod($multipartUploadProcessor, 'getPart', [$request, 2]);
        $this->assertTrue(is_array($partTwo));
        [$contentType, $body] = $partTwo;

        $this->assertEquals('image/gif', $contentType);
        $this->assertEquals($image, $body);

        // Clean up
        fclose($resource);
        unlink($tempFile);
    }

    protected function createMultipartRequest($jsonData, $binaryContent): Request
    {
        $boundary = uniqid();
        $content = $this->createMultipartContent($boundary, $jsonData, $binaryContent);

        return $this->createMultipartRequestWithContent($boundary, $content);
    }

    protected function createMultipartContent($boundary, $jsonData, $binaryContent): string
    {
        $content = '--'.$boundary."\r\n".'Content-Type: application/json; charset=UTF-8'."\r\n\r\n".$jsonData."\r\n\r\n";
        $content .= '--'.$boundary."\r\n".'Content-Type: image/gif'."\r\n\r\n".$binaryContent."\r\n\r\n";

        return $content . ('--'.$boundary.'--');
    }

    protected function createMultipartRequestWithContent($boundary, $content): Request
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($content));

        $request->headers = new HeaderBag(['Content-Type' => 'multipart/related; boundary="'.$boundary.'"']);

        return $request;
    }

    protected function getProcessor(): MultipartUploadProcessor
    {
        return $this->createMock(MultipartUploadProcessor::class);
    }
}
