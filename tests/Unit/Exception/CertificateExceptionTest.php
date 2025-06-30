<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Exception\CertificateException;

class CertificateExceptionTest extends TestCase
{
    public function testExceptionIsCreated(): void
    {
        $message = '证书错误';
        $exception = new CertificateException($message);

        $this->assertInstanceOf(CertificateException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
}