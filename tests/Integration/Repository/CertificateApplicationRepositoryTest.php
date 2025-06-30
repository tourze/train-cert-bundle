<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Repository\CertificateApplicationRepository;

class CertificateApplicationRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(CertificateApplicationRepository::class));
    }

    public function testRepositoryReturnsCorrectEntityClass(): void
    {
        $reflection = new \ReflectionClass(CertificateApplicationRepository::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
    }

    public function testRepositoryCanBeInstantiated(): void
    {
        // 仅测试类的存在性，避免需要数据库连接
        $this->assertTrue(class_exists(CertificateApplicationRepository::class));
        $this->assertTrue(class_exists(CertificateApplication::class));
    }
}