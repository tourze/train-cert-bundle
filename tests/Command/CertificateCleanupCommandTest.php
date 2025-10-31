<?php

namespace Tourze\TrainCertBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainCertBundle\Command\CertificateCleanupCommand;

/**
 * @internal
 */
#[CoversClass(CertificateCleanupCommand::class)]
#[RunTestsInSeparateProcesses]
final class CertificateCleanupCommandTest extends AbstractCommandTestCase
{
    private CertificateCleanupCommand $command;

    private CommandTester $commandTester;

    protected function onSetUp(): void        // 测试初始化逻辑
    {
        $command = self::getContainer()->get(CertificateCleanupCommand::class);
        $this->assertInstanceOf(CertificateCleanupCommand::class, $command);
        $this->command = $command;

        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    public function testExecuteWithDryRun(): void
    {
        $this->commandTester->execute([
            '--dry-run' => true,
            '--expired-days' => 365,
            '--verification-days' => 90,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('试运行模式', $this->commandTester->getDisplay());
    }

    public function testExecuteWithForceOption(): void
    {
        $this->commandTester->execute([
            '--force' => true,
            '--expired-days' => 365,
            '--verification-days' => 90,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('清理结果', $this->commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $this->assertEquals('certificate:cleanup', $this->command->getName());
        $this->assertEquals('清理过期和无效证书', $this->command->getDescription());

        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('expired-days'));
        $this->assertTrue($definition->hasOption('verification-days'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('batch-size'));
        $this->assertTrue($definition->hasOption('force'));
    }

    public function testOptionExpiredDays(): void
    {
        $this->commandTester->execute([
            '--expired-days' => 30,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('证书数据清理', $output);
    }

    public function testOptionVerificationDays(): void
    {
        $this->commandTester->execute([
            '--verification-days' => 60,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('证书数据清理', $output);
    }

    public function testOptionDryRun(): void
    {
        $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('试运行模式', $output);
    }

    public function testOptionBatchSize(): void
    {
        $this->commandTester->execute([
            '--batch-size' => 50,
            '--dry-run' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('证书数据清理', $output);
    }

    public function testOptionForce(): void
    {
        $this->commandTester->execute([
            '--force' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('清理结果', $output);
    }
}
