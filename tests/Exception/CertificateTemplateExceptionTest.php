<?php

namespace Tourze\TrainCertBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainCertBundle\Exception\CertificateTemplateException;

/**
 * @internal
 */
#[CoversClass(CertificateTemplateException::class)]
final class CertificateTemplateExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionIsCreated(): void
    {
        $message = '证书模板错误';
        $exception = new CertificateTemplateException($message);

        $this->assertEquals($message, $exception->getMessage());
    }
}
