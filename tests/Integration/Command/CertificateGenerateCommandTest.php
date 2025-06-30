<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Integration\Command;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\TrainCertBundle\Command\CertificateGenerateCommand;
use Tourze\TrainCertBundle\Service\CertificateGeneratorService;
use Tourze\TrainCertBundle\Service\CertificateTemplateService;

class CertificateGenerateCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $generatorService = $this->createMock(CertificateGeneratorService::class);
        $templateService = $this->createMock(CertificateTemplateService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $command = new CertificateGenerateCommand($generatorService, $templateService, $logger);
        $this->assertEquals('certificate:generate', $command->getName());
    }

    public function testCommandDescription(): void
    {
        $generatorService = $this->createMock(CertificateGeneratorService::class);
        $templateService = $this->createMock(CertificateTemplateService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $command = new CertificateGenerateCommand($generatorService, $templateService, $logger);
        $this->assertNotEmpty($command->getDescription());
    }

    public function testCommandExists(): void
    {
        $this->assertTrue(class_exists(CertificateGenerateCommand::class));
    }
}