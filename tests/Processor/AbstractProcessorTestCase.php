<?php

namespace SRIO\RestUploadBundle\Tests\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionClass;
use ReflectionMethod;
use SRIO\RestUploadBundle\Tests\Upload\AbstractUploadTestCase;

abstract class AbstractProcessorTestCase extends AbstractUploadTestCase
{
    /**
     * Call an object method, even if it is private or protected.
     */
    protected function callMethod($object, $methodName, array $arguments): mixed
    {
        $method = $this->getMethod($object::class, $methodName);

        return $method->invokeArgs($object, $arguments);
    }

    /**
     * Get a protected method as public.
     */
    protected function getMethod($className, $name): ReflectionMethod
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
