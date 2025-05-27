<?php

namespace Tourze\TrainCertBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainCertBundle\Repository\CertificateRecordRepository;
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;

/**
 * 证书清理命令
 * 清理过期和无效的证书数据
 */
#[AsCommand(
    name: 'certificate:cleanup',
    description: '清理过期和无效证书',
)]
class CertificateCleanupCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CertificateRecordRepository $recordRepository,
        private readonly CertificateVerificationRepository $verificationRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('expired-days', 'd', InputOption::VALUE_OPTIONAL, '清理过期多少天的证书', 365)
            ->addOption('verification-days', 'v', InputOption::VALUE_OPTIONAL, '清理多少天前的验证记录', 90)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '试运行模式，不实际删除数据')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, '批处理大小', 100)
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制执行，跳过确认')
            ->setHelp('
此命令用于清理过期和无效的证书数据，包括：
- 清理过期超过指定天数的证书记录
- 清理旧的验证记录
- 清理无效的临时文件

示例:
  # 清理过期超过1年的证书
  php bin/console certificate:cleanup --expired-days=365

  # 清理90天前的验证记录
  php bin/console certificate:cleanup --verification-days=90

  # 试运行模式
  php bin/console certificate:cleanup --dry-run

  # 强制执行
  php bin/console certificate:cleanup --force
');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $expiredDays = (int) $input->getOption('expired-days');
            $verificationDays = (int) $input->getOption('verification-days');
            $batchSize = (int) $input->getOption('batch-size');
            $isDryRun = $input->getOption('dry-run');
            $isForce = $input->getOption('force');

            $io->title('证书数据清理');

            if ($isDryRun) {
                $io->warning('试运行模式 - 不会实际删除数据');
            }

            // 分析待清理的数据
            $cleanupStats = $this->analyzeCleanupData($expiredDays, $verificationDays);
            
            $this->displayCleanupPlan($cleanupStats, $io);

            // 确认执行
            if (!$isForce && !$isDryRun) {
                if (!$io->confirm('确认执行清理操作？此操作不可逆！', false)) {
                    $io->info('操作已取消');
                    return Command::SUCCESS;
                }
            }

            // 执行清理
            $results = $this->performCleanup($cleanupStats, $batchSize, $isDryRun, $io);

            $this->displayResults($results, $io);

            $this->logger->info('证书清理任务完成', [
                'expiredDays' => $expiredDays,
                'verificationDays' => $verificationDays,
                'isDryRun' => $isDryRun,
                'results' => $results,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error(sprintf('清理任务失败: %s', $e->getMessage()));
            $this->logger->error('证书清理任务失败', ['error' => $e]);
            return Command::FAILURE;
        }
    }

    /**
     * 分析待清理的数据
     */
    private function analyzeCleanupData(int $expiredDays, int $verificationDays): array
    {
        $expiredDate = new \DateTime();
        $expiredDate->sub(new \DateInterval("P{$expiredDays}D"));

        $verificationDate = new \DateTime();
        $verificationDate->sub(new \DateInterval("P{$verificationDays}D"));

        // 查找过期证书
        $expiredRecords = $this->findExpiredRecords($expiredDate);
        
        // 查找旧验证记录
        $oldVerifications = $this->findOldVerifications($verificationDate);

        return [
            'expiredRecords' => $expiredRecords,
            'oldVerifications' => $oldVerifications,
            'expiredDate' => $expiredDate,
            'verificationDate' => $verificationDate,
        ];
    }

    /**
     * 查找过期证书记录
     */
    private function findExpiredRecords(\DateTimeInterface $expiredDate): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('cr')
            ->from('Tourze\TrainCertBundle\Entity\CertificateRecord', 'cr')
            ->where('cr.expiryDate < :expiredDate')
            ->setParameter('expiredDate', $expiredDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找旧验证记录
     */
    private function findOldVerifications(\DateTimeInterface $verificationDate): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('cv')
            ->from('Tourze\TrainCertBundle\Entity\CertificateVerification', 'cv')
            ->where('cv.verificationTime < :verificationDate')
            ->setParameter('verificationDate', $verificationDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * 显示清理计划
     */
    private function displayCleanupPlan(array $cleanupStats, SymfonyStyle $io): void
    {
        $io->section('清理计划');

        $io->table(
            ['清理项目', '数量', '截止日期'],
            [
                [
                    '过期证书记录',
                    count($cleanupStats['expiredRecords']),
                    $cleanupStats['expiredDate']->format('Y-m-d')
                ],
                [
                    '旧验证记录',
                    count($cleanupStats['oldVerifications']),
                    $cleanupStats['verificationDate']->format('Y-m-d')
                ],
            ]
        );

        $totalItems = count($cleanupStats['expiredRecords']) + count($cleanupStats['oldVerifications']);
        $io->info(sprintf('总计待清理项目: %d', $totalItems));
    }

    /**
     * 执行清理操作
     */
    private function performCleanup(
        array $cleanupStats,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io
    ): array {
        $results = [
            'expiredRecordsDeleted' => 0,
            'verificationsDeleted' => 0,
            'errors' => [],
        ];

        // 清理过期证书记录
        if (!empty($cleanupStats['expiredRecords'])) {
            $io->section('清理过期证书记录');
            $results['expiredRecordsDeleted'] = $this->cleanupExpiredRecords(
                $cleanupStats['expiredRecords'],
                $batchSize,
                $isDryRun,
                $io
            );
        }

        // 清理旧验证记录
        if (!empty($cleanupStats['oldVerifications'])) {
            $io->section('清理旧验证记录');
            $results['verificationsDeleted'] = $this->cleanupOldVerifications(
                $cleanupStats['oldVerifications'],
                $batchSize,
                $isDryRun,
                $io
            );
        }

        return $results;
    }

    /**
     * 清理过期证书记录
     */
    private function cleanupExpiredRecords(
        array $expiredRecords,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io
    ): int {
        $deleted = 0;
        $batches = array_chunk($expiredRecords, $batchSize);

        $io->progressStart(count($expiredRecords));

        foreach ($batches as $batch) {
            try {
                if (!$isDryRun) {
                    foreach ($batch as $record) {
                        $this->entityManager->remove($record);
                    }
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }

                $deleted += count($batch);
                $io->progressAdvance(count($batch));

            } catch (\Exception $e) {
                $this->logger->error('清理过期证书记录失败', [
                    'batchSize' => count($batch),
                    'error' => $e,
                ]);

                if ($io->isVerbose()) {
                    $io->warning(sprintf('批次清理失败: %s', $e->getMessage()));
                }
            }
        }

        $io->progressFinish();
        return $deleted;
    }

    /**
     * 清理旧验证记录
     */
    private function cleanupOldVerifications(
        array $oldVerifications,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io
    ): int {
        $deleted = 0;
        $batches = array_chunk($oldVerifications, $batchSize);

        $io->progressStart(count($oldVerifications));

        foreach ($batches as $batch) {
            try {
                if (!$isDryRun) {
                    foreach ($batch as $verification) {
                        $this->entityManager->remove($verification);
                    }
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }

                $deleted += count($batch);
                $io->progressAdvance(count($batch));

            } catch (\Exception $e) {
                $this->logger->error('清理验证记录失败', [
                    'batchSize' => count($batch),
                    'error' => $e,
                ]);

                if ($io->isVerbose()) {
                    $io->warning(sprintf('批次清理失败: %s', $e->getMessage()));
                }
            }
        }

        $io->progressFinish();
        return $deleted;
    }

    /**
     * 显示清理结果
     */
    private function displayResults(array $results, SymfonyStyle $io): void
    {
        $io->section('清理结果');

        $io->table(
            ['清理项目', '删除数量'],
            [
                ['过期证书记录', $results['expiredRecordsDeleted']],
                ['旧验证记录', $results['verificationsDeleted']],
            ]
        );

        $totalDeleted = $results['expiredRecordsDeleted'] + $results['verificationsDeleted'];
        
        if ($totalDeleted > 0) {
            $io->success(sprintf('清理完成，共删除 %d 条记录', $totalDeleted));
        } else {
            $io->info('没有需要清理的数据');
        }

        if (!empty($results['errors'])) {
            $io->warning(sprintf('清理过程中发生 %d 个错误，请检查日志', count($results['errors'])));
        }
    }
} 