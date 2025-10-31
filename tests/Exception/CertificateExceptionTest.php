<?php

namespace Tourze\TrainCertBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainCertBundle\Exception\CertificateException;

/**
 * @internal
 */
#[CoversClass(CertificateException::class)]
final class CertificateExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionIsCreated(): void
    {
        $message = '证书错误';
        $exception = new CertificateException($message);

        $this->assertEquals($message, $exception->getMessage());
    }
}
