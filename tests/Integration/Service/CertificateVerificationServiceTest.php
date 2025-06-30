<?php

namespace Tourze\TrainCertBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Service\CertificateVerificationService;

class CertificateVerificationServiceTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(CertificateVerificationService::class));
    }
}