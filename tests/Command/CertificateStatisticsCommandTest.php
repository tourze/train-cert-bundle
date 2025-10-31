<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainCertBundle\Command\CertificateStatisticsCommand;

/**
 * @internal
 */
#[CoversClass(CertificateStatisticsCommand::class)]
#[RunTestsInSeparateProcesses]
final class CertificateStatisticsCommandTest extends AbstractCommandTestCase
{
    private CertificateStatisticsCommand $command;

    protected function onSetUp(): void        // 测试初始化逻辑
    {
        $command = self::getContainer()->get(CertificateStatisticsCommand::class);
        $this->assertInstanceOf(CertificateStatisticsCommand::class, $command);
        $this->command = $command;
    }

    protected function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add($this->command);

        return new CommandTester($this->command);
    }

    public function testCommandExecution(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('证书统计', $output);
    }

    public function testCommandName(): void
    {
        $this->assertEquals('certificate:statistics', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
    }

    public function testOptionStartDate(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--start-date' => '2024-01-01',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('证书统计', $output);
    }

    public function testOptionEndDate(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--end-date' => '2024-12-31',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('证书统计', $output);
    }

    public function testOptionFormat(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--format' => 'json',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('证书统计', $output);
    }

    public function testOptionOutputFile(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--output-file' => '/tmp/test_stats.json',
            '--format' => 'json',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('证书统计', $output);
    }

    public function testOptionType(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--type' => 'safety',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('证书统计', $output);
    }
}
