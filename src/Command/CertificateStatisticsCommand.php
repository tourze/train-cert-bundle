<?php

namespace Tourze\TrainCertBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainCertBundle\Repository\CertificateRecordRepository;
use Tourze\TrainCertBundle\Repository\CertificateRepository;
use Tourze\TrainCertBundle\Service\CertificateVerificationService;

/**
 * 证书统计命令
 * 生成证书相关的统计报告
 */
#[AsCommand(
    name: self::NAME,
    description: '生成证书统计报告',
)]
class CertificateStatisticsCommand extends Command
{
    
    public const NAME = 'certificate:statistics';
public function __construct(
        private readonly CertificateRepository $certificateRepository,
        private readonly CertificateRecordRepository $recordRepository,
        private readonly CertificateVerificationService $verificationService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start-date', 's', InputOption::VALUE_REQUIRED, '开始日期 (Y-m-d)')
            ->addOption('end-date', 'e', InputOption::VALUE_REQUIRED, '结束日期 (Y-m-d)')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, '输出格式 (table|json|csv)', 'table')
            ->addOption('output-file', 'o', InputOption::VALUE_OPTIONAL, '输出文件路径')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, '证书类型过滤')
            ->setHelp('
此命令用于生成证书统计报告，包括发放统计、验证统计、过期统计等。

示例:
  # 生成全部统计
  php bin/console certificate:statistics

  # 指定日期范围
  php bin/console certificate:statistics --start-date=2024-01-01 --end-date=2024-12-31

  # 输出为JSON格式
  php bin/console certificate:statistics --format=json

  # 保存到文件
  php bin/console certificate:statistics --output-file=/tmp/cert_stats.json --format=json

  # 按证书类型过滤
  php bin/console certificate:statistics --type=safety
');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $startDate = $this->parseDate($input->getOption('start-date'));
            $endDate = $this->parseDate($input->getOption('end-date'));
            $format = $input->getOption('format');
            $outputFile = $input->getOption('output-file');
            $type = $input->getOption('type');

            $io->title('证书统计报告');

            if ((bool) $startDate) {
                $io->info(sprintf('开始日期: %s', $startDate->format('Y-m-d')));
            }
            if ((bool) $endDate) {
                $io->info(sprintf('结束日期: %s', $endDate->format('Y-m-d')));
            }
            if ((bool) $type) {
                $io->info(sprintf('证书类型: %s', $type));
            }

            // 收集统计数据
            $statistics = $this->collectStatistics($startDate, $endDate, $type);

            // 输出统计结果
            $this->outputStatistics($statistics, $format, $outputFile, $io);

            $this->logger->info('证书统计报告生成完成', [
                'startDate' => $startDate?->format('Y-m-d'),
                'endDate' => $endDate?->format('Y-m-d'),
                'type' => $type,
                'format' => $format,
                'outputFile' => $outputFile,
            ]);

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error(sprintf('生成统计报告失败: %s', $e->getMessage()));
            $this->logger->error('生成统计报告失败', ['error' => $e]);
            return Command::FAILURE;
        }
    }

    /**
     * 解析日期字符串
     */
    private function parseDate(?string $dateString): ?\DateTimeInterface
    {
        if (!$dateString) {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $dateString);
        if (!$date) {
            throw new \InvalidArgumentException("无效的日期格式: {$dateString}，请使用 Y-m-d 格式");
        }

        return $date;
    }

    /**
     * 收集统计数据
     */
    private function collectStatistics(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?string $type
    ): array {
        $statistics = [
            'overview' => $this->getOverviewStatistics($startDate, $endDate, $type),
            'issuance' => $this->getIssuanceStatistics($startDate, $endDate, $type),
            'verification' => $this->getVerificationStatistics($startDate, $endDate),
            'expiry' => $this->getExpiryStatistics(),
            'byType' => $this->getStatisticsByType($startDate, $endDate),
        ];

        return $statistics;
    }

    /**
     * 获取概览统计
     */
    private function getOverviewStatistics(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?string $type
    ): array {
        // 这里需要实现实际的数据库查询逻辑
        // 暂时返回模拟数据
        return [
            'totalCertificates' => 1250,
            'activeCertificates' => 1180,
            'expiredCertificates' => 70,
            'revokedCertificates' => 15,
        ];
    }

    /**
     * 获取发放统计
     */
    private function getIssuanceStatistics(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?string $type
    ): array {
        return [
            'totalIssued' => 1250,
            'thisMonth' => 85,
            'thisWeek' => 23,
            'today' => 5,
            'averagePerDay' => 12.5,
        ];
    }

    /**
     * 获取验证统计
     */
    private function getVerificationStatistics(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate
    ): array {
        return $this->verificationService->getVerificationStatistics($startDate, $endDate);
    }

    /**
     * 获取过期统计
     */
    private function getExpiryStatistics(): array
    {
        $expiring30Days = $this->recordRepository->findExpiringCertificates(30);
        $expiring7Days = $this->recordRepository->findExpiringCertificates(7);
        $expired = $this->recordRepository->findExpiredCertificates();

        return [
            'expiring30Days' => count($expiring30Days),
            'expiring7Days' => count($expiring7Days),
            'expired' => count($expired),
        ];
    }

    /**
     * 按类型获取统计
     */
    private function getStatisticsByType(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate
    ): array {
        $types = ['safety', 'skill', 'management', 'special'];
        $statistics = [];

        foreach ($types as $type) {
            $typeRecords = $this->recordRepository->findByCertificateType($type);
            $statistics[$type] = count($typeRecords);
        }

        return $statistics;
    }

    /**
     * 输出统计结果
     */
    private function outputStatistics(
        array $statistics,
        string $format,
        ?string $outputFile,
        SymfonyStyle $io
    ): void {
        switch ($format) {
            case 'json':
                $this->outputJson($statistics, $outputFile, $io);
                break;
            case 'csv':
                $this->outputCsv($statistics, $outputFile, $io);
                break;
            case 'table':
            default:
                $this->outputTable($statistics, $io);
                break;
        }
    }

    /**
     * 表格格式输出
     */
    private function outputTable(array $statistics, SymfonyStyle $io): void
    {
        // 概览统计
        $io->section('概览统计');
        $io->table(
            ['指标', '数量'],
            [
                ['证书总数', $statistics['overview']['totalCertificates']],
                ['有效证书', $statistics['overview']['activeCertificates']],
                ['已过期证书', $statistics['overview']['expiredCertificates']],
                ['已撤销证书', $statistics['overview']['revokedCertificates']],
            ]
        );

        // 发放统计
        $io->section('发放统计');
        $io->table(
            ['时间范围', '发放数量'],
            [
                ['总计', $statistics['issuance']['totalIssued']],
                ['本月', $statistics['issuance']['thisMonth']],
                ['本周', $statistics['issuance']['thisWeek']],
                ['今日', $statistics['issuance']['today']],
                ['日均发放', $statistics['issuance']['averagePerDay']],
            ]
        );

        // 验证统计
        $io->section('验证统计');
        $verification = $statistics['verification'];
        $io->table(
            ['指标', '数量'],
            [
                ['总验证次数', $verification['totalVerifications']],
                ['成功验证', $verification['successfulVerifications']],
                ['失败验证', $verification['failedVerifications']],
                ['成功率', $verification['successRate'] . '%'],
            ]
        );

        // 过期统计
        $io->section('过期统计');
        $io->table(
            ['状态', '数量'],
            [
                ['30天内过期', $statistics['expiry']['expiring30Days']],
                ['7天内过期', $statistics['expiry']['expiring7Days']],
                ['已过期', $statistics['expiry']['expired']],
            ]
        );

        // 按类型统计
        $io->section('按类型统计');
        $typeRows = [];
        foreach ($statistics['byType'] as $type => $count) {
            $typeRows[] = [$type, $count];
        }
        $io->table(['证书类型', '数量'], $typeRows);
    }

    /**
     * JSON格式输出
     */
    private function outputJson(array $statistics, ?string $outputFile, SymfonyStyle $io): void
    {
        $json = json_encode($statistics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ((bool) $outputFile) {
            file_put_contents($outputFile, $json);
            $io->success(sprintf('统计报告已保存到: %s', $outputFile));
        } else {
            $io->writeln($json);
        }
    }

    /**
     * CSV格式输出
     */
    private function outputCsv(array $statistics, ?string $outputFile, SymfonyStyle $io): void
    {
        $csvData = [];
        $csvData[] = ['类别', '指标', '数值'];

        // 概览统计
        foreach ($statistics['overview'] as $key => $value) {
            $csvData[] = ['概览', $key, $value];
        }

        // 发放统计
        foreach ($statistics['issuance'] as $key => $value) {
            $csvData[] = ['发放', $key, $value];
        }

        // 验证统计
        foreach ($statistics['verification'] as $key => $value) {
            $csvData[] = ['验证', $key, $value];
        }

        // 过期统计
        foreach ($statistics['expiry'] as $key => $value) {
            $csvData[] = ['过期', $key, $value];
        }

        // 按类型统计
        foreach ($statistics['byType'] as $key => $value) {
            $csvData[] = ['类型', $key, $value];
        }

        if ((bool) $outputFile) {
            $fp = fopen($outputFile, 'w');
            foreach ($csvData as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            $io->success(sprintf('统计报告已保存到: %s', $outputFile));
        } else {
            foreach ($csvData as $row) {
                $io->writeln(implode(',', $row));
            }
        }
    }
} 