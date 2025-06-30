<?php

namespace Tourze\TrainCertBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Service\CertificateNotificationService;

class CertificateNotificationServiceTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(CertificateNotificationService::class));
    }
}