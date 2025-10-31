<?php

namespace Tourze\TrainCertBundle\Command;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;
use Tourze\TrainCertBundle\Service\CertificateGeneratorService;
use Tourze\TrainCertBundle\Service\CertificateTemplateService;

/**
 * 证书生成命令
 * 支持单个和批量证书生成
 */
#[AsCommand(name: self::NAME, description: '生成证书')]
#[WithMonologChannel(channel: 'train_cert')]
class CertificateGenerateCommand extends Command
{
    public const NAME = 'certificate:generate';

    public function __construct(
        private readonly CertificateGeneratorService $generatorService,
        private readonly CertificateTemplateService $templateService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('template-id', InputArgument::REQUIRED, '证书模板ID')
            ->addOption('user-ids', 'u', InputOption::VALUE_REQUIRED, '用户ID列表（逗号分隔）')
            ->addOption('user-file', 'f', InputOption::VALUE_REQUIRED, '包含用户ID的文件路径')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, '批处理大小', 100)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '试运行模式，不实际生成证书')
            ->addOption('output-dir', 'o', InputOption::VALUE_OPTIONAL, '输出目录', '/tmp/certificates')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $templateIdArg = $input->getArgument('template-id');
        $batchSizeOption = $input->getOption('batch-size');

        if (!is_string($templateIdArg)) {
            $io->error('模板ID必须为字符串');

            return Command::FAILURE;
        }

        $templateId = $templateIdArg;
        $userIds = $this->getUserIds($input);
        $batchSize = is_numeric($batchSizeOption) ? (int) $batchSizeOption : 100;
        $isDryRun = (bool) $input->getOption('dry-run');

        if (0 === count($userIds)) {
            $io->error('必须提供用户ID列表');

            return Command::FAILURE;
        }

        // 验证模板
        if (!$this->validateTemplate($templateId, $io)) {
            return Command::FAILURE;
        }

        $io->title('证书生成任务');
        $io->info(sprintf('模板ID: %s', $templateId));
        $io->info(sprintf('用户数量: %d', count($userIds)));
        $io->info(sprintf('批处理大小: %d', $batchSize));

        if ($isDryRun) {
            $io->warning('试运行模式 - 不会实际生成证书');
        }

        if (!$io->confirm('确认开始生成证书？', false)) {
            $io->info('操作已取消');

            return Command::SUCCESS;
        }

        return $this->generateCertificates($userIds, $templateId, $batchSize, $isDryRun, $io);
    }

    /**
     * 获取用户ID列表
     *
     * @return array<string>
     */
    private function getUserIds(InputInterface $input): array
    {
        $userIds = [];

        // 从命令行参数获取
        $userIdsOption = $input->getOption('user-ids');
        if (is_string($userIdsOption) && '' !== $userIdsOption) {
            $userIds = array_map('trim', explode(',', $userIdsOption));
        }

        // 从文件获取
        $userFile = $input->getOption('user-file');
        if (is_string($userFile) && '' !== $userFile) {
            if (!file_exists($userFile)) {
                throw new InvalidArgumentException("用户文件不存在: {$userFile}");
            }

            $fileContent = file($userFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (false === $fileContent) {
                throw new InvalidArgumentException("无法读取用户文件: {$userFile}");
            }
            $fileUserIds = array_filter(
                array_map('trim', $fileContent),
                static fn (string $userId): bool => '' !== $userId
            );
            $userIds = array_merge($userIds, $fileUserIds);
        }

        return array_unique($userIds);
    }

    /**
     * 验证模板
     */
    private function validateTemplate(string $templateId, SymfonyStyle $io): bool
    {
        try {
            $templates = $this->templateService->getAvailableTemplates();
            $templateExists = false;

            foreach ($templates as $template) {
                if ($template->getId() === $templateId) {
                    $templateExists = true;
                    $io->info(sprintf('模板名称: %s', $template->getTemplateName()));
                    $io->info(sprintf('模板类型: %s', $template->getTemplateType()));
                    break;
                }
            }

            if (!$templateExists) {
                $io->error(sprintf('模板不存在: %s', $templateId));

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $io->error(sprintf('验证模板失败: %s', $e->getMessage()));
            $this->logger->error('验证模板失败', ['templateId' => $templateId, 'error' => $e]);

            return false;
        }
    }

    /**
     * 生成证书
     *
     * @param array<string> $userIds
     */
    private function generateCertificates(
        array $userIds,
        string $templateId,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io,
    ): int {
        $totalUsers = count($userIds);
        $successCount = 0;
        $batches = array_chunk($userIds, max(1, $batchSize));

        $io->progressStart($totalUsers);

        foreach ($batches as $batchIndex => $batch) {
            $io->section(sprintf('处理批次 %d/%d', $batchIndex + 1, count($batches)));
            $successCount += $this->processCertificateBatch($batch, $templateId, $isDryRun, $io);
        }

        $io->progressFinish();

        return $successCount;
    }

    /**
     * 处理证书生成批次
     * @param array<string> $batch
     */
    private function processCertificateBatch(
        array $batch,
        string $templateId,
        bool $isDryRun,
        SymfonyStyle $io,
    ): int {
        $successCount = 0;

        foreach ($batch as $userId) {
            if ($this->generateSingleUserCertificate($userId, $templateId, $isDryRun, $io)) {
                ++$successCount;
            }
            $io->progressAdvance();
        }

        return $successCount;
    }

    /**
     * 为单个用户生成证书
     */
    private function generateSingleUserCertificate(
        string $userId,
        string $templateId,
        bool $isDryRun,
        SymfonyStyle $io,
    ): bool {
        try {
            if (!$isDryRun) {
                $certificate = $this->generatorService->generateSingleCertificate(
                    $userId,
                    $templateId,
                    ['issuingAuthority' => '培训管理系统']
                );

                $this->logger->info('证书生成成功', [
                    'userId' => $userId,
                    'certificateId' => $certificate->getId(),
                    'templateId' => $templateId,
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            $this->logCertificateGenerationError($userId, $templateId, $e, $io);

            return false;
        }
    }

    /**
     * 记录证书生成错误
     */
    private function logCertificateGenerationError(
        string $userId,
        string $templateId,
        \Throwable $e,
        SymfonyStyle $io,
    ): void {
        $this->logger->error('证书生成失败', [
            'userId' => $userId,
            'templateId' => $templateId,
            'error' => $e,
        ]);

        if ($io->isVerbose()) {
            $io->warning(sprintf('用户 %s 证书生成失败: %s', $userId, $e->getMessage()));
        }
    }
}
