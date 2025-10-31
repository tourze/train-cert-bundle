<?php

namespace Tourze\TrainCertBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;

/**
 * @internal
 */
#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionIsCreated(): void
    {
        $message = '无效参数';
        $exception = new InvalidArgumentException($message);

        $this->assertEquals($message, $exception->getMessage());
    }
}
