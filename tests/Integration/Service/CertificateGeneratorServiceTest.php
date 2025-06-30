<?php

namespace Tourze\TrainCertBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Service\CertificateGeneratorService;

class CertificateGeneratorServiceTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(CertificateGeneratorService::class));
    }
}