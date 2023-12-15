<?php

namespace SRIO\RestUploadBundle\Tests\Upload\Processor;

use SRIO\RestUploadBundle\Request\RequestContentHandler;
use SRIO\RestUploadBundle\Tests\Upload\AbstractUploadTestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestContentHandlerTest extends AbstractUploadTestCase
{
    public function testBinaryStringContent(): void
    {
        $client = $this->getNewClient();
        $filePath = $this->getResourcePath($client, 'apple.gif');
        $content = file_get_contents($filePath);

        $this->doTest($content, $content);
    }

    public function testBinaryResourceContent(): void
    {
        $client = $this->getNewClient();
        $filePath = $this->getResourcePath($client, 'apple.gif');
        $content = fopen($filePath, 'r');
        $expectedContent = file_get_contents($filePath);

        $this->doTest($expectedContent, $content);
    }

    public function testStringContent(): void
    {
        $client = $this->getNewClient();
        $filePath = $this->getResourcePath($client, 'lorem.txt');
        $content = file_get_contents($filePath);

        $this->doTest($content, $content);
    }

    public function testStringResourceContent(): void
    {
        $client = $this->getNewClient();
        $filePath = $this->getResourcePath($client, 'lorem.txt');
        $content = fopen($filePath, 'r');
        $expectedContent = file_get_contents($filePath);

        $this->doTest($expectedContent, $content);
    }

    /**
     * @param false|resource|string $content
     */
    protected function doTest(string|false $expectedContent, $content): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($content));

        $handler = new RequestContentHandler($request);
        $this->assertFalse($handler->eof());

        $foundContent = '';
        // @phpstan-ignore-next-line
        while (!$handler->eof()) {
            $foundContent .= $handler->gets();
        }

        // @phpstan-ignore-next-line
        $this->assertEquals($expectedContent, $foundContent);
        $this->assertTrue($handler->eof());
    }
}
