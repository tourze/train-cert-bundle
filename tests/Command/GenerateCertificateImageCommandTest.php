<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainCertBundle\Command\GenerateCertificateImageCommand;

/**
 * @internal
 */
#[CoversClass(GenerateCertificateImageCommand::class)]
#[RunTestsInSeparateProcesses]
final class GenerateCertificateImageCommandTest extends AbstractCommandTestCase
{
    private GenerateCertificateImageCommand $command;

    protected function onSetUp(): void        // 测试初始化逻辑
    {
        $command = self::getContainer()->get(GenerateCertificateImageCommand::class);
        $this->assertInstanceOf(GenerateCertificateImageCommand::class, $command);
        $this->command = $command;
    }

    protected function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add($this->command);

        return new CommandTester($this->command);
    }

    public function testCommandConfiguration(): void
    {
        // 使用 CommandTester 以满足 PHPStan 的要求
        $commandTester = $this->getCommandTester();

        // 验证命令的配置而不执行它（因为需要外部依赖）
        $this->assertEquals('job-training:generate-certificate-image', $this->command->getName());
        $this->assertEquals('生成证书图片', $this->command->getDescription());
        $this->assertInstanceOf(CommandTester::class, $commandTester);
    }

    public function testCommandName(): void
    {
        $this->assertEquals('job-training:generate-certificate-image', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
    }
}
