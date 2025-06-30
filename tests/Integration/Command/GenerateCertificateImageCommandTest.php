<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Integration\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\TrainCertBundle\Command\GenerateCertificateImageCommand;

class GenerateCertificateImageCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        
        $command = new GenerateCertificateImageCommand($kernel);
        $this->assertEquals('job-training:generate-certificate-image', $command->getName());
    }

    public function testCommandDescription(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        
        $command = new GenerateCertificateImageCommand($kernel);
        $this->assertNotEmpty($command->getDescription());
    }

    public function testCommandExists(): void
    {
        $this->assertTrue(class_exists(GenerateCertificateImageCommand::class));
    }
}