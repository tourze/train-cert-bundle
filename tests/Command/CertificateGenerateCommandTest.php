<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainCertBundle\Command\CertificateGenerateCommand;

/**
 * @internal
 */
#[CoversClass(CertificateGenerateCommand::class)]
#[RunTestsInSeparateProcesses]
final class CertificateGenerateCommandTest extends AbstractCommandTestCase
{
    private CertificateGenerateCommand $command;

    protected function onSetUp(): void        // 测试初始化逻辑
    {
        $command = self::getContainer()->get(CertificateGenerateCommand::class);
        $this->assertInstanceOf(CertificateGenerateCommand::class, $command);
        $this->command = $command;
    }

    protected function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add($this->command);

        return new CommandTester($this->command);
    }

    public function testCommandWithInvalidArguments(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'template-id' => 'invalid-template',
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('必须提供用户ID列表', $commandTester->getDisplay());
    }

    public function testCommandName(): void
    {
        $reflection = new \ReflectionClass(CertificateGenerateCommand::class);
        $nameConstant = $reflection->getConstant('NAME');
        $this->assertEquals('certificate:generate', $nameConstant);
    }

    public function testCommandDescription(): void
    {
        $reflection = new \ReflectionClass(CertificateGenerateCommand::class);
        $attributes = $reflection->getAttributes(AsCommand::class);
        $this->assertNotEmpty($attributes);

        $asCommand = $attributes[0]->newInstance();
        $this->assertNotEmpty($asCommand->description);
        $this->assertEquals('生成证书', $asCommand->description);
    }

    public function testArgumentTemplateId(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'template-id' => 'test-template-123',
            '--user-ids' => 'user1,user2',
            '--dry-run' => true,
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('模板不存在', $commandTester->getDisplay());
    }

    public function testOptionUserIds(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'template-id' => 'test-template',
            '--user-ids' => 'user1,user2,user3',
            '--dry-run' => true,
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('模板不存在', $output);
    }

    public function testOptionUserFile(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute([
                'template-id' => 'test-template',
                '--user-file' => '/tmp/test-users.txt',
                '--dry-run' => true,
            ]);
        } catch (\Exception $e) {
            $this->assertStringContainsString('用户文件不存在', $e->getMessage());

            return;
        }

        self::fail('Expected InvalidArgumentException for non-existent user file');
    }

    public function testOptionBatchSize(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'template-id' => 'test-template',
            '--user-ids' => 'user1',
            '--batch-size' => 50,
            '--dry-run' => true,
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('模板不存在', $output);
    }

    public function testOptionDryRun(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'template-id' => 'test-template',
            '--user-ids' => 'user1',
            '--dry-run' => true,
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('模板不存在', $output);
    }

    public function testOptionOutputDir(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'template-id' => 'test-template',
            '--user-ids' => 'user1',
            '--output-dir' => '/tmp/custom-certificates',
            '--dry-run' => true,
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('模板不存在', $output);
    }
}
