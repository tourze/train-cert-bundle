<?php

namespace Tourze\TrainCertBundle\Command;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainCertBundle\Exception\CertificateException;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;
use Tourze\TrainCertBundle\Repository\CertificateRecordRepository;
use Tourze\TrainCertBundle\Service\CertificateVerificationService;

/**
 * 证书统计命令
 * 生成证书相关的统计报告
 */
#[AsCommand(name: self::NAME, description: '生成证书统计报告')]
#[WithMonologChannel(channel: 'train_cert')]
class CertificateStatisticsCommand extends Command
{
    public const NAME = 'certificate:statistics';

    public function __construct(
        private readonly CertificateRecordRepository $recordRepository,
        private readonly CertificateVerificationService $verificationService,
        private readonly LoggerInterface $logger,
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $startDateOption = $input->getOption('start-date');
            $endDateOption = $input->getOption('end-date');
            $formatOption = $input->getOption('format');
            $outputFileOption = $input->getOption('output-file');
            $typeOption = $input->getOption('type');

            $startDate = $this->parseDate(is_string($startDateOption) ? $startDateOption : null);
            $endDate = $this->parseDate(is_string($endDateOption) ? $endDateOption : null);
            $format = is_string($formatOption) ? $formatOption : 'table';
            $outputFile = is_string($outputFileOption) ? $outputFileOption : null;
            $type = is_string($typeOption) ? $typeOption : null;

            $io->title('证书统计报告');

            if ((bool) $startDate) {
                $io->info(sprintf('开始日期: %s', $startDate->format('Y-m-d')));
            }
            if ((bool) $endDate) {
                $io->info(sprintf('结束日期: %s', $endDate->format('Y-m-d')));
            }
            if (null !== $type) {
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
        if (null === $dateString || '' === $dateString) {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $dateString);
        if (false === $date) {
            throw new InvalidArgumentException("无效的日期格式: {$dateString}，请使用 Y-m-d 格式");
        }

        return $date;
    }

    /**
     * 收集统计数据
     *
     * @return array{
     *     overview: array<string, int>,
     *     issuance: array<string, int|float>,
     *     verification: array<string, mixed>,
     *     expiry: array<string, int>,
     *     byType: array<string, int>
     * }
     */
    private function collectStatistics(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?string $type,
    ): array {
        return [
            'overview' => $this->getOverviewStatistics($startDate, $endDate, $type),
            'issuance' => $this->getIssuanceStatistics($startDate, $endDate, $type),
            'verification' => $this->getVerificationStatistics($startDate, $endDate),
            'expiry' => $this->getExpiryStatistics(),
            'byType' => $this->getStatisticsByType($startDate, $endDate),
        ];
    }

    /**
     * 获取概览统计
     *
     * @return array<string, int>
     */
    private function getOverviewStatistics(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?string $type,
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
     *
     * @return array<string, int|float>
     */
    private function getIssuanceStatistics(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?string $type,
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
     *
     * @return array<string, mixed>
     */
    private function getVerificationStatistics(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
    ): array {
        return $this->verificationService->getVerificationStatistics($startDate, $endDate);
    }

    /**
     * 获取过期统计
     *
     * @return array<string, int>
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
     *
     * @return array<string, int>
     */
    private function getStatisticsByType(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
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
     * @param array<string, mixed> $statistics
     */
    private function outputStatistics(
        array $statistics,
        string $format,
        ?string $outputFile,
        SymfonyStyle $io,
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
     * @param array<string, mixed> $statistics
     */
    private function outputTable(array $statistics, SymfonyStyle $io): void
    {
        // 概览统计
        $io->section('概览统计');
        $overview = $statistics['overview'];
        assert(is_array($overview), 'overview statistics must be an array');
        $io->table(
            ['指标', '数量'],
            [
                ['证书总数', $overview['totalCertificates'] ?? 0],
                ['有效证书', $overview['activeCertificates'] ?? 0],
                ['已过期证书', $overview['expiredCertificates'] ?? 0],
                ['已撤销证书', $overview['revokedCertificates'] ?? 0],
            ]
        );

        // 发放统计
        $io->section('发放统计');
        $issuance = $statistics['issuance'];
        assert(is_array($issuance), 'issuance statistics must be an array');
        $io->table(
            ['时间范围', '发放数量'],
            [
                ['总计', $issuance['totalIssued'] ?? 0],
                ['本月', $issuance['thisMonth'] ?? 0],
                ['本周', $issuance['thisWeek'] ?? 0],
                ['今日', $issuance['today'] ?? 0],
                ['日均发放', $issuance['averagePerDay'] ?? 0],
            ]
        );

        // 验证统计
        $io->section('验证统计');
        $verification = $statistics['verification'];
        assert(is_array($verification), 'verification statistics must be an array');
        $io->table(
            ['指标', '数量'],
            [
                ['总验证次数', $verification['totalVerifications'] ?? 0],
                ['成功验证', $verification['successfulVerifications'] ?? 0],
                ['失败验证', $verification['failedVerifications'] ?? 0],
                ['成功率', sprintf('%.1f%%', is_numeric($verification['successRate'] ?? 0) ? (float) ($verification['successRate'] ?? 0) : 0.0)],
            ]
        );

        // 过期统计
        $io->section('过期统计');
        $expiry = $statistics['expiry'];
        assert(is_array($expiry), 'expiry statistics must be an array');
        $io->table(
            ['状态', '数量'],
            [
                ['30天内过期', $expiry['expiring30Days'] ?? 0],
                ['7天内过期', $expiry['expiring7Days'] ?? 0],
                ['已过期', $expiry['expired'] ?? 0],
            ]
        );

        // 按类型统计
        $io->section('按类型统计');
        $byType = $statistics['byType'];
        assert(is_array($byType), 'byType statistics must be an array');
        $typeRows = [];
        foreach ($byType as $type => $count) {
            $typeRows[] = [$type, $count];
        }
        $io->table(['证书类型', '数量'], $typeRows);
    }

    /**
     * JSON格式输出
     * @param array<string, mixed> $statistics
     */
    private function outputJson(array $statistics, ?string $outputFile, SymfonyStyle $io): void
    {
        $json = json_encode($statistics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (false === $json) {
            throw new CertificateException('无法编码统计数据为JSON格式');
        }

        if (null !== $outputFile) {
            file_put_contents($outputFile, $json);
            $io->success(sprintf('统计报告已保存到: %s', $outputFile));
        } else {
            $io->writeln($json);
        }
    }

    /**
     * CSV格式输出
     * @param array<string, mixed> $statistics
     */
    private function outputCsv(array $statistics, ?string $outputFile, SymfonyStyle $io): void
    {
        $csvData = $this->buildCsvData($statistics);

        if (null !== $outputFile) {
            $this->writeCsvToFile($csvData, $outputFile, $io);
        } else {
            $this->outputCsvToConsole($csvData, $io);
        }
    }

    /**
     * 构建CSV数据
     * @param array<string, mixed> $statistics
     * @return array<array<string>>
     */
    private function buildCsvData(array $statistics): array
    {
        $csvData = [['类别', '指标', '数值']];

        $categories = [
            '概览' => 'overview',
            '发放' => 'issuance',
            '验证' => 'verification',
            '过期' => 'expiry',
            '类型' => 'byType',
        ];

        foreach ($categories as $categoryName => $categoryKey) {
            $categoryData = $statistics[$categoryKey];
            assert(is_array($categoryData), 'category data must be an array');
            /** @var array<string, mixed> $categoryData */
            $csvData = array_merge($csvData, $this->addCategoryData($categoryData, $categoryName));
        }

        return $csvData;
    }

    /**
     * 添加分类数据
     * @param array<string, mixed> $categoryData
     * @return array<array<string>>
     */
    /**
     * 添加分类数据
     * @param array<string, mixed> $categoryData
     * @return array<array<string>>
     */
    private function addCategoryData(array $categoryData, string $categoryName): array
    {
        $data = [];
        foreach ($categoryData as $key => $value) {
            $stringValue = is_scalar($value) ? (string) $value : '';
            $data[] = [$categoryName, (string) $key, $stringValue];
        }

        return $data;
    }

    /**
     * 写入CSV文件
     * @param array<array<string>> $csvData
     */
    private function writeCsvToFile(array $csvData, string $outputFile, SymfonyStyle $io): void
    {
        $fp = fopen($outputFile, 'w');
        if (false === $fp) {
            throw new CertificateException("无法创建输出文件: {$outputFile}");
        }
        foreach ($csvData as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        $io->success(sprintf('统计报告已保存到: %s', $outputFile));
    }

    /**
     * 输出CSV到控制台
     * @param array<array<string>> $csvData
     */
    private function outputCsvToConsole(array $csvData, SymfonyStyle $io): void
    {
        foreach ($csvData as $row) {
            $io->writeln(implode(',', $row));
        }
    }
}
