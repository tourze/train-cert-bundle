<?php

namespace Tourze\TrainCertBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainCertBundle\Entity\CertificateRecord;
use Tourze\TrainCertBundle\Entity\CertificateVerification;
use Tourze\TrainCertBundle\Repository\CertificateRecordRepository;
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;

/**
 * 证书清理命令
 * 清理过期和无效的证书数据
 */
#[AsCommand(name: self::NAME, description: '清理过期和无效证书')]
#[WithMonologChannel(channel: 'train_cert')]
class CertificateCleanupCommand extends Command
{
    public const NAME = 'certificate:cleanup';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CertificateRecordRepository $certificateRecordRepository,
        private readonly CertificateVerificationRepository $certificateVerificationRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('expired-days', 'd', InputOption::VALUE_OPTIONAL, '清理过期多少天的证书', 365)
            ->addOption('verification-days', null, InputOption::VALUE_OPTIONAL, '清理多少天前的验证记录', 90)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '试运行模式，不实际删除数据')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, '批处理大小', 100)
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制执行，跳过确认')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $expiredDaysOption = $input->getOption('expired-days');
            $verificationDaysOption = $input->getOption('verification-days');
            $batchSizeOption = $input->getOption('batch-size');

            $expiredDays = is_numeric($expiredDaysOption) ? (int) $expiredDaysOption : 365;
            $verificationDays = is_numeric($verificationDaysOption) ? (int) $verificationDaysOption : 90;
            $batchSize = is_numeric($batchSizeOption) ? (int) $batchSizeOption : 100;
            $isDryRun = (bool) $input->getOption('dry-run');
            $isForce = (bool) $input->getOption('force');

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
        } catch (\Throwable $e) {
            $io->error(sprintf('清理任务失败: %s', $e->getMessage()));
            $this->logger->error('证书清理任务失败', ['error' => $e]);

            return Command::FAILURE;
        }
    }

    /**
     * 分析待清理的数据
     *
     * @return array{
     *     expiredRecords: array<CertificateRecord>,
     *     oldVerifications: array<CertificateVerification>,
     *     expiredDate: \DateTimeInterface,
     *     verificationDate: \DateTimeInterface
     * }
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
     *
     * @return array<CertificateRecord>
     */
    private function findExpiredRecords(\DateTimeInterface $expiredDate): array
    {
        return $this->certificateRecordRepository->findExpiredBefore($expiredDate);
    }

    /**
     * 查找旧验证记录
     *
     * @return array<CertificateVerification>
     */
    private function findOldVerifications(\DateTimeInterface $verificationDate): array
    {
        return $this->certificateVerificationRepository->findVerificationsBeforeDate($verificationDate);
    }

    /**
     * 显示清理计划
     *
     * @param array{
     *     expiredRecords: array<CertificateRecord>,
     *     oldVerifications: array<CertificateVerification>,
     *     expiredDate: \DateTimeInterface,
     *     verificationDate: \DateTimeInterface
     * } $cleanupStats
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
                    $cleanupStats['expiredDate']->format('Y-m-d'),
                ],
                [
                    '旧验证记录',
                    count($cleanupStats['oldVerifications']),
                    $cleanupStats['verificationDate']->format('Y-m-d'),
                ],
            ]
        );

        $totalItems = count($cleanupStats['expiredRecords']) + count($cleanupStats['oldVerifications']);
        $io->info(sprintf('总计待清理项目: %d', $totalItems));
    }

    /**
     * 执行清理操作
     * @param array<string, mixed> $cleanupStats
     * @return array<string, mixed>
     */
    private function performCleanup(
        array $cleanupStats,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io,
    ): array {
        $results = [
            'expiredRecordsDeleted' => 0,
            'verificationsDeleted' => 0,
            'errors' => [],
        ];

        // 清理过期证书记录
        $expiredRecords = $cleanupStats['expiredRecords'];
        assert(is_array($expiredRecords), 'expiredRecords must be an array');
        /** @var array<CertificateRecord> $expiredRecords */
        if (count($expiredRecords) > 0) {
            $io->section('清理过期证书记录');
            $results['expiredRecordsDeleted'] = $this->cleanupExpiredRecords(
                $expiredRecords,
                $batchSize,
                $isDryRun,
                $io
            );
        }

        // 清理旧验证记录
        $oldVerifications = $cleanupStats['oldVerifications'];
        assert(is_array($oldVerifications), 'oldVerifications must be an array');
        /** @var array<CertificateVerification> $oldVerifications */
        if (count($oldVerifications) > 0) {
            $io->section('清理旧验证记录');
            $results['verificationsDeleted'] = $this->cleanupOldVerifications(
                $oldVerifications,
                $batchSize,
                $isDryRun,
                $io
            );
        }

        return $results;
    }

    /**
     * 清理过期证书记录
     * @param array<CertificateRecord> $expiredRecords
     */
    private function cleanupExpiredRecords(
        array $expiredRecords,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io,
    ): int {
        return $this->cleanupEntities($expiredRecords, $batchSize, $isDryRun, $io, '清理过期证书记录失败');
    }

    /**
     * 清理旧验证记录
     * @param array<CertificateVerification> $oldVerifications
     */
    private function cleanupOldVerifications(
        array $oldVerifications,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io,
    ): int {
        return $this->cleanupEntities($oldVerifications, $batchSize, $isDryRun, $io, '清理验证记录失败');
    }

    /**
     * 通用实体清理方法
     * @param object[] $entities
     */
    private function cleanupEntities(
        array $entities,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io,
        string $errorMessage,
    ): int {
        $deleted = 0;
        $batches = array_chunk($entities, max(1, $batchSize));

        $io->progressStart(count($entities));

        foreach ($batches as $batch) {
            $deleted += $this->processBatch($batch, $isDryRun, $io, $errorMessage);
        }

        $io->progressFinish();

        return $deleted;
    }

    /**
     * 处理单个批次
     * @param object[] $batch
     */
    private function processBatch(
        array $batch,
        bool $isDryRun,
        SymfonyStyle $io,
        string $errorMessage,
    ): int {
        try {
            if (!$isDryRun) {
                $this->removeBatchEntities($batch);
            }

            $io->progressAdvance(count($batch));

            return count($batch);
        } catch (\Throwable $e) {
            $this->logBatchError($e, $batch, $errorMessage, $io);

            return 0;
        }
    }

    /**
     * 移除批次中的实体
     * @param object[] $batch
     */
    private function removeBatchEntities(array $batch): void
    {
        foreach ($batch as $entity) {
            $this->entityManager->remove($entity);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * 记录批次错误
     * @param object[] $batch
     */
    private function logBatchError(\Throwable $e, array $batch, string $errorMessage, SymfonyStyle $io): void
    {
        $this->logger->error($errorMessage, [
            'batchSize' => count($batch),
            'error' => $e,
        ]);

        if ($io->isVerbose()) {
            $io->warning(sprintf('批次清理失败: %s', $e->getMessage()));
        }
    }

    /**
     * 显示清理结果
     * @param array<string, mixed> $results
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

        $expiredDeleted = $results['expiredRecordsDeleted'];
        $verificationsDeleted = $results['verificationsDeleted'];
        $totalDeleted = (is_numeric($expiredDeleted) ? (int) $expiredDeleted : 0) + (is_numeric($verificationsDeleted) ? (int) $verificationsDeleted : 0);

        if ($totalDeleted > 0) {
            $io->success(sprintf('清理完成，共删除 %d 条记录', $totalDeleted));
        } else {
            $io->info('没有需要清理的数据');
        }

        $errors = $results['errors'];
        if (is_countable($errors) && count($errors) > 0) {
            $io->warning(sprintf('清理过程中发生 %d 个错误，请检查日志', count($errors)));
        }
    }
}
