<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Integration\Command;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\TrainCertBundle\Command\CertificateStatisticsCommand;
use Tourze\TrainCertBundle\Repository\CertificateRecordRepository;
use Tourze\TrainCertBundle\Service\CertificateVerificationService;

class CertificateStatisticsCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $recordRepository = $this->createMock(CertificateRecordRepository::class);
        $verificationService = $this->createMock(CertificateVerificationService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $command = new CertificateStatisticsCommand($recordRepository, $verificationService, $logger);
        $this->assertEquals('certificate:statistics', $command->getName());
    }

    public function testCommandDescription(): void
    {
        $recordRepository = $this->createMock(CertificateRecordRepository::class);
        $verificationService = $this->createMock(CertificateVerificationService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $command = new CertificateStatisticsCommand($recordRepository, $verificationService, $logger);
        $this->assertNotEmpty($command->getDescription());
    }

    public function testCommandExists(): void
    {
        $this->assertTrue(class_exists(CertificateStatisticsCommand::class));
    }
}