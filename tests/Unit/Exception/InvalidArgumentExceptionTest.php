<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;

class InvalidArgumentExceptionTest extends TestCase
{
    public function testExceptionIsCreated(): void
    {
        $message = '无效参数';
        $exception = new InvalidArgumentException($message);

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
}