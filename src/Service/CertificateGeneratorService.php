<?php

namespace Tourze\TrainCertBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateRecord;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Repository\CertificateRepository;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;

/**
 * 证书生成服务类
 * 负责证书的生成、批量生成、验证码生成等功能
 */
class CertificateGeneratorService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CertificateRepository $certificateRepository,
        private readonly CertificateTemplateRepository $templateRepository
    ) {
    }

    /**
     * 生成单个证书
     * 
     * @param string $userId 用户ID
     * @param string $templateId 模板ID
     * @param array $data 证书数据
     * @return Certificate 生成的证书
     */
    public function generateSingleCertificate(string $userId, string $templateId, array $data): Certificate
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('证书模板不存在');
        }

        if (!$template->isActive()) {
            throw new \InvalidArgumentException('证书模板未启用');
        }

        // 获取用户对象（暂时简化处理）
        $user = $this->getUserById($userId);
        if (!$user) {
            throw new \InvalidArgumentException('用户不存在');
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
        $record->setIssueDate(new \DateTime());
        $record->setExpiryDate($this->calculateExpiryDate($template));
        $record->setIssuingAuthority($data['issuingAuthority'] ?? '培训机构');
        $record->setVerificationCode($this->generateVerificationCode($certificate->getId()));
        $record->setMetadata($data);

        $this->entityManager->persist($certificate);
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $certificate;
    }

    /**
     * 批量生成证书
     * 
     * @param array $userIds 用户ID列表
     * @param string $templateId 模板ID
     * @param array $config 配置参数
     * @return Certificate[] 生成的证书列表
     */
    public function generateBatchCertificates(array $userIds, string $templateId, array $config): array
    {
        $certificates = [];
        
        foreach ($userIds as $userId) {
            try {
                $certificate = $this->generateSingleCertificate($userId, $templateId, $config);
                $certificates[] = $certificate;
            } catch (\Exception $e) {
                // 记录错误但继续处理其他用户
                error_log("生成证书失败 - 用户ID: {$userId}, 错误: " . $e->getMessage());
            }
        }

        return $certificates;
    }

    /**
     * 生成验证码
     * 
     * @param string $certificateId 证书ID
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
        $sequence = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        
        return "CERT-{$year}{$month}{$day}-{$sequence}";
    }

    /**
     * 生成证书文件
     * 
     * @param CertificateTemplate $template 证书模板
     * @param array $data 证书数据
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
     * @return \DateTimeInterface 到期日期
     */
    private function calculateExpiryDate(CertificateTemplate $template): \DateTimeInterface
    {
        $config = $template->getTemplateConfig();
        $validityPeriod = $config['validityPeriod'] ?? 365; // 默认1年有效期
        
        $expiryDate = new \DateTime();
        $expiryDate->add(new \DateInterval("P{$validityPeriod}D"));
        
        return $expiryDate;
    }

    /**
     * 根据用户ID获取用户对象
     * 注意：这里需要用户服务的支持，暂时返回null，实际使用时需要注入用户服务
     * 
     * @param string $userId 用户ID
     * @return UserInterface|null
     */
    private function getUserById(string $userId): ?UserInterface
    {
        // TODO: 实际实现中需要注入用户服务来获取用户对象
        // 这里暂时返回null，需要在实际使用时完善
        return null;
    }

    /**
     * 预览证书
     * 
     * @param string $templateId 模板ID
     * @param array $sampleData 示例数据
     * @return string 预览URL
     */
    public function previewCertificate(string $templateId, array $sampleData): string
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('证书模板不存在');
        }

        // 生成预览文件
        return $this->generateCertificateFile($template, $sampleData);
    }

    /**
     * 验证证书数据
     * 
     * @param array $data 证书数据
     * @return bool 是否有效
     */
    public function validateCertificateData(array $data): bool
    {
        $requiredFields = ['userId', 'templateId'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        return true;
    }
} 