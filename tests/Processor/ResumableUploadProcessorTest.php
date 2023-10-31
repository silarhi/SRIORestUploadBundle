<?php

namespace SRIO\RestUploadBundle\Tests\Processor;

use SRIO\RestUploadBundle\Exception\UploadProcessorException;
use SRIO\RestUploadBundle\Processor\ResumableUploadProcessor;
use SRIO\RestUploadBundle\Tests\Fixtures\Entity\ResumableUploadSession;
use SRIO\RestUploadBundle\Upload\StorageHandler;

class ResumableUploadProcessorTest extends AbstractProcessorTestCase
{
    /**
     * @dataProvider contentSuccessRangeDataProvider
     */
    public function testSuccessComputeContentRange(string $string, int|string|null $start, int|string|null $end, int|null $length): void
    {
        $result = $this->callParseContentRange($string);
        $this->assertTrue(is_array($result));
        $this->assertEquals($start, $result['start']);
        $this->assertEquals($end, $result['end']);
        $this->assertEquals($length, $result['total']);
    }

    /**
     * @dataProvider contentErrorRangeDataProvider
     */
    public function testErrorComputeContentRange(string $string): void
    {
        $this->expectException(UploadProcessorException::class);
        $this->callParseContentRange($string);
    }

    /**
     * Call parseContentRange function.
     */
    protected function callParseContentRange(string $string): mixed
    {
        $storageHandler = $this->createMock(StorageHandler::class);

        $method = $this->getMethod(ResumableUploadProcessor::class, 'parseContentRange');
        $em = $this->getMockEntityManager();
        $uploadProcessor = new ResumableUploadProcessor($storageHandler, $em, ResumableUploadSession::class);

        return $method->invokeArgs($uploadProcessor, [$string]);
    }

    /**
     * Data Provider for success Content-Range test.
     */
    public static function contentSuccessRangeDataProvider(): array
    {
        return [['bytes 1-2/12', 1, 2, 12], ['bytes */1000', '*', null, 1000], ['bytes 0-1000/1000', 0, 1000, 1000]];
    }

    /**
     * Data Provider for error Content-Range test.
     */
    public static function contentErrorRangeDataProvider(): array
    {
        return [['bytes 2-1/12'], ['bytes 12/12'], ['bytes 0-13/12'], ['1-2/12']];
    }
}
