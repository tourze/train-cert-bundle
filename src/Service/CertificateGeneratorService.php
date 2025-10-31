<?php

namespace Tourze\TrainCertBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateRecord;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;
use Tourze\UserServiceContracts\UserManagerInterface;

/**
 * 证书生成服务类
 * 负责证书的生成、批量生成、验证码生成等功能
 */
#[WithMonologChannel(channel: 'train_cert')]
#[Autoconfigure(public: true)]
class CertificateGeneratorService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CertificateTemplateRepository $templateRepository,
        private readonly LoggerInterface $logger,
        private readonly UserManagerInterface $userManager,
    ) {
    }

    /**
     * 生成单个证书
     *
     * @param string               $userId     用户ID
     * @param string               $templateId 模板ID
     * @param array<string, mixed> $data       证书数据
     *
     * @return Certificate 生成的证书
     */
    public function generateSingleCertificate(string $userId, string $templateId, array $data): Certificate
    {
        $template = $this->templateRepository->find($templateId);
        if (null === $template) {
            throw new InvalidArgumentException('证书模板不存在');
        }

        if (true !== $template->isActive()) {
            throw new InvalidArgumentException('证书模板未启用');
        }

        // 通过用户管理器获取用户
        $user = $this->userManager->loadUserByIdentifier($userId);
        if (null === $user) {
            throw new InvalidArgumentException("用户不存在: {$userId}");
        }

        // 创建证书
        $certificate = new Certificate();
        $certificate->setUser($user);
        $certificate->setTitle($template->getTemplateName());
        $certificate->setValid(true);

        // 生成证书文件（这里需要实际的证书生成逻辑）
        $certificateUrl = $this->generateCertificateFile($template, $data);
        $certificate->setImgUrl($certificateUrl);

        // 创建证书记录
        $record = new CertificateRecord();
        $record->setCertificate($certificate);
        $record->setCertificateNumber($this->generateCertificateNumber());
        $record->setCertificateType($template->getTemplateType());
        $record->setIssueDate(new \DateTimeImmutable());
        $record->setExpiryDate($this->calculateExpiryDate($template));
        $issuingAuthority = $data['issuingAuthority'] ?? '培训机构';
        assert(is_string($issuingAuthority));
        $record->setIssuingAuthority($issuingAuthority);
        // 先持久化以获取证书ID，然后设置验证码
        $this->entityManager->persist($certificate);
        $this->entityManager->flush();

        $record->setVerificationCode($this->generateVerificationCode((string) $certificate->getId()));
        $record->setMetadata($data);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $certificate;
    }

    /**
     * 批量生成证书
     *
     * @param array<string>        $userIds    用户ID列表
     * @param string               $templateId 模板ID
     * @param array<string, mixed> $config     配置参数
     *
     * @return Certificate[] 生成的证书列表
     */
    public function generateBatchCertificates(array $userIds, string $templateId, array $config): array
    {
        $certificates = [];

        foreach ($userIds as $userId) {
            try {
                $certificate = $this->generateSingleCertificate($userId, $templateId, $config);
                $certificates[] = $certificate;
            } catch (\Throwable $e) {
                // 记录错误但继续处理其他用户
                $this->logger->error("生成证书失败 - 用户ID: {$userId}, 错误: " . $e->getMessage());
            }
        }

        return $certificates;
    }

    /**
     * 生成验证码
     *
     * @param string $certificateId 证书ID
     *
     * @return string 验证码
     */
    public function generateVerificationCode(string $certificateId): string
    {
        // 生成基于证书ID和时间戳的验证码
        $timestamp = time();
        $hash = hash('sha256', $certificateId . $timestamp . 'CERT_VERIFY_SALT');

        // 取前12位作为验证码
        return strtoupper(substr($hash, 0, 12));
    }

    /**
     * 生成证书编号
     *
     * @return string 证书编号
     */
    private function generateCertificateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');

        // 生成格式：CERT-YYYYMMDD-XXXXXX
        $sequence = str_pad((string) mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

        return "CERT-{$year}{$month}{$day}-{$sequence}";
    }

    /**
     * 生成证书文件
     *
     * @param CertificateTemplate  $template 证书模板
     * @param array<string, mixed> $data     证书数据
     *
     * @return string 证书文件URL
     */
    private function generateCertificateFile(CertificateTemplate $template, array $data): string
    {
        // TODO: 实现实际的证书文件生成逻辑
        // 这里需要根据模板和数据生成PDF或图片格式的证书

        // 暂时返回一个占位符URL
        $filename = 'certificate_' . uniqid() . '.pdf';

        return '/certificates/' . $filename;
    }

    /**
     * 计算证书到期日期
     *
     * @param CertificateTemplate $template 证书模板
     *
     * @return \DateTimeInterface 到期日期
     */
    private function calculateExpiryDate(CertificateTemplate $template): \DateTimeInterface
    {
        $config = $template->getTemplateConfig();
        $validityPeriod = $config['validityPeriod'] ?? 365; // 默认1年有效期

        $expiryDate = new \DateTimeImmutable();

        $validityPeriodString = (string) (is_numeric($validityPeriod) ? (int) $validityPeriod : 365);

        return $expiryDate->add(new \DateInterval("P{$validityPeriodString}D"));
    }

    /**
     * 预览证书
     *
     * @param string               $templateId 模板ID
     * @param array<string, mixed> $sampleData 示例数据
     *
     * @return string 预览URL
     */
    public function previewCertificate(string $templateId, array $sampleData): string
    {
        $template = $this->templateRepository->find($templateId);
        if (null === $template) {
            throw new InvalidArgumentException('证书模板不存在');
        }

        // 生成预览文件
        return $this->generateCertificateFile($template, $sampleData);
    }

    /**
     * 验证证书数据
     *
     * @param array<string, mixed> $data 证书数据
     *
     * @return bool 是否有效
     */
    public function validateCertificateData(array $data): bool
    {
        $requiredFields = ['userId', 'templateId'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || '' === $data[$field]) {
                return false;
            }
        }

        return true;
    }
}
