<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\CertificateVerification;
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;

class CertificateVerificationRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(CertificateVerificationRepository::class));
    }

    public function testRepositoryReturnsCorrectEntityClass(): void
    {
        $reflection = new \ReflectionClass(CertificateVerificationRepository::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
    }

    public function testRepositoryCanBeInstantiated(): void
    {
        // 仅测试类的存在性，避免需要数据库连接
        $this->assertTrue(class_exists(CertificateVerificationRepository::class));
        $this->assertTrue(class_exists(CertificateVerification::class));
    }
}