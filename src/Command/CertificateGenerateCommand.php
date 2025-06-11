<?php

namespace Tourze\TrainCertBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainCertBundle\Service\CertificateGeneratorService;
use Tourze\TrainCertBundle\Service\CertificateTemplateService;

/**
 * 证书生成命令
 * 支持单个和批量证书生成
 */
#[AsCommand(
    name: 'certificate:generate',
    description: '生成证书',
)]
class CertificateGenerateCommand extends Command
{
    public function __construct(
        private readonly CertificateGeneratorService $generatorService,
        private readonly CertificateTemplateService $templateService,
        private readonly LoggerInterface $logger
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
            ->setHelp('
此命令用于生成证书。可以为单个用户或批量用户生成证书。

示例:
  # 为单个用户生成证书
  php bin/console certificate:generate template123 --user-ids=user456

  # 为多个用户批量生成证书
  php bin/console certificate:generate template123 --user-ids=user1,user2,user3

  # 从文件读取用户ID列表
  php bin/console certificate:generate template123 --user-file=/path/to/users.txt

  # 试运行模式
  php bin/console certificate:generate template123 --user-ids=user456 --dry-run
');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $templateId = $input->getArgument('template-id');
        $userIds = $this->getUserIds($input);
        $batchSize = (int) $input->getOption('batch-size');
        $isDryRun = $input->getOption('dry-run');

        if (empty($userIds)) {
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
     */
    private function getUserIds(InputInterface $input): array
    {
        $userIds = [];

        // 从命令行参数获取
        if ($userIdsOption = $input->getOption('user-ids')) {
            $userIds = array_map('trim', explode(',', $userIdsOption));
        }

        // 从文件获取
        if ($userFile = $input->getOption('user-file')) {
            if (!file_exists($userFile)) {
                throw new \InvalidArgumentException("用户文件不存在: {$userFile}");
            }

            $fileUserIds = array_filter(
                array_map('trim', file($userFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
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
        } catch  (\Throwable $e) {
            $io->error(sprintf('验证模板失败: %s', $e->getMessage()));
            $this->logger->error('验证模板失败', ['templateId' => $templateId, 'error' => $e]);
            return false;
        }
    }

    /**
     * 生成证书
     */
    private function generateCertificates(
        array $userIds,
        string $templateId,
        int $batchSize,
        bool $isDryRun,
        SymfonyStyle $io
    ): int {
        $totalUsers = count($userIds);
        $successCount = 0;
        $failureCount = 0;
        $batches = array_chunk($userIds, $batchSize);

        $io->progressStart($totalUsers);

        foreach ($batches as $batchIndex => $batch) {
            $io->section(sprintf('处理批次 %d/%d', $batchIndex + 1, count($batches)));

            foreach ($batch as $userId) {
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

                    $successCount++;
                    $io->progressAdvance();

                } catch  (\Throwable $e) {
                    $failureCount++;
                    $io->progressAdvance();

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

            // 批次间短暂休息，避免系统负载过高
            if ($batchIndex < count($batches) - 1) {
                usleep(100000); // 0.1秒
            }
        }

        $io->progressFinish();

        // 显示结果统计
        $io->success('证书生成任务完成');
        $io->table(
            ['统计项', '数量'],
            [
                ['总用户数', $totalUsers],
                ['成功生成', $successCount],
                ['生成失败', $failureCount],
                ['成功率', sprintf('%.2f%%', $totalUsers > 0 ? ($successCount / $totalUsers) * 100 : 0)],
            ]
        );

        if ($failureCount > 0) {
            $io->warning(sprintf('有 %d 个证书生成失败，请检查日志获取详细信息', $failureCount));
        }

        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
} 