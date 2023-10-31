<?php

namespace SRIO\RestUploadBundle\Tests\Processor;

use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionMethod;
use SRIO\RestUploadBundle\Processor\MultipartUploadProcessor;
use SRIO\RestUploadBundle\Tests\Upload\AbstractUploadTestCase;

abstract class AbstractProcessorTestCase extends AbstractUploadTestCase
{
    /**
     * Call an object method, even if it is private or protected.
     */
    protected function callMethod(MultipartUploadProcessor $object, string $methodName, array $arguments): mixed
    {
        $method = $this->getMethod($object::class, $methodName);

        return $method->invokeArgs($object, $arguments);
    }

    /**
     * Get a protected method as public.
     *
     * @param class-string|string $className
     *
     * @psalm-param '\SRIO\RestUploadBundle\Processor\ResumableUploadProcessor'|class-string $className
     * @psalm-param 'parseContentRange' $name
     */
    protected function getMethod(string $className, string $name): ReflectionMethod
    {
        $class = new ReflectionClass($className);

        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    protected function getMockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }
}
