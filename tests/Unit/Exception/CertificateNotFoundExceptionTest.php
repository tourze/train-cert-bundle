<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Exception\CertificateNotFoundException;

class CertificateNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsCreated(): void
    {
        $message = '证书未找到';
        $exception = new CertificateNotFoundException($message);

        $this->assertInstanceOf(CertificateNotFoundException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
}