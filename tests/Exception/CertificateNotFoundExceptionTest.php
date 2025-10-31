<?php

namespace Tourze\TrainCertBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainCertBundle\Exception\CertificateNotFoundException;

/**
 * @internal
 */
#[CoversClass(CertificateNotFoundException::class)]
final class CertificateNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionIsCreated(): void
    {
        $message = '证书未找到';
        $exception = new CertificateNotFoundException($message);

        $this->assertEquals($message, $exception->getMessage());
    }
}
