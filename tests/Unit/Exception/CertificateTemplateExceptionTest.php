<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Exception\CertificateTemplateException;

class CertificateTemplateExceptionTest extends TestCase
{
    public function testExceptionIsCreated(): void
    {
        $message = '证书模板错误';
        $exception = new CertificateTemplateException($message);

        $this->assertInstanceOf(CertificateTemplateException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
}