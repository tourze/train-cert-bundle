<?php

namespace Tourze\TrainCertBundle\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TrainCertBundle\Command\CertificateCleanupCommand;

class CertificateCleanupCommandTest extends TestCase
{
    private CertificateCleanupCommand $command;
    private EntityManagerInterface&MockObject $entityManager;
    private LoggerInterface&MockObject $logger;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->command = new CertificateCleanupCommand(
            $this->entityManager,
            $this->logger
        );

        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithDryRun(): void
    {
        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        $this->entityManager->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->any())
            ->method('getResult')
            ->willReturn([]);

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
        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        $this->entityManager->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->any())
            ->method('getResult')
            ->willReturn([]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('证书清理任务完成', $this->isType('array'));

        $this->commandTester->execute([
            '--force' => true,
            '--expired-days' => 365,
            '--verification-days' => 90,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteHandlesException(): void
    {
        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('证书清理任务失败', $this->isType('array'));

        $this->commandTester->execute([
            '--force' => true,
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('清理任务失败', $this->commandTester->getDisplay());
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
}