<?php

namespace Tourze\TrainCertBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateAudit;
use Tourze\TrainCertBundle\Repository\CertificateApplicationRepository;
use Tourze\TrainCertBundle\Repository\CertificateRepository;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;

/**
 * 证书服务类
 * 负责证书管理的核心业务逻辑，包括证书生成、申请、审核、发放等功能
 */
class CertificateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CertificateRepository $certificateRepository,
        private readonly CertificateApplicationRepository $applicationRepository,
        private readonly CertificateTemplateRepository $templateRepository,
        private readonly CertificateGeneratorService $generatorService
    ) {
    }

    /**
     * 生成证书
     * 
     * @param string $userId 用户ID
     * @param string $courseId 课程ID（暂时保留，未来可能需要）
     * @param string $templateId 模板ID
     * @return Certificate 生成的证书
     */
    public function generateCertificate(string $userId, string $courseId, string $templateId): Certificate
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('证书模板不存在');
        }

        if (!$template->isActive()) {
            throw new \InvalidArgumentException('证书模板未启用');
        }

        // 使用生成器服务生成证书
        $certificateData = [
            'userId' => $userId,
            'courseId' => $courseId,
            'templateId' => $templateId,
        ];

        return $this->generatorService->generateSingleCertificate($userId, $templateId, $certificateData);
    }

    /**
     * 申请证书
     * 
     * @param string $userId 申请用户ID
     * @param string $courseId 课程ID（暂时保留）
     * @param array $applicationData 申请数据
     * @return CertificateApplication 证书申请记录
     */
    public function applyCertificate(string $userId, string $courseId, array $applicationData): CertificateApplication
    {
        // 验证申请数据
        $this->validateApplicationData($applicationData);

        // 获取用户对象（这里需要用户服务，暂时简化处理）
        $user = $this->getUserById($userId);
        if (!$user) {
            throw new \InvalidArgumentException('用户不存在');
        }

        // 获取证书模板
        $templateId = $applicationData['templateId'] ?? null;
        if (!$templateId) {
            throw new \InvalidArgumentException('必须指定证书模板');
        }

        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('证书模板不存在');
        }

        // 创建申请记录
        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType($applicationData['type'] ?? 'standard');
        $application->setApplicationStatus('pending');
        $application->setApplicationData($applicationData);
        $application->setRequiredDocuments($applicationData['requiredDocuments'] ?? []);
        $application->setApplicationTime(new \DateTime());

        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $application;
    }

    /**
     * 审核证书申请
     * 
     * @param string $applicationId 申请ID
     * @param string $auditResult 审核结果
     * @param string $comment 审核意见
     * @return CertificateAudit 审核记录
     */
    public function auditCertificate(string $applicationId, string $auditResult, string $comment): CertificateAudit
    {
        $application = $this->applicationRepository->find($applicationId);
        if (!$application) {
            throw new \InvalidArgumentException('证书申请不存在');
        }

        if ($application->getApplicationStatus() !== 'pending') {
            throw new \InvalidArgumentException('申请状态不允许审核');
        }

        // 创建审核记录
        $audit = new CertificateAudit();
        $audit->setApplication($application);
        $audit->setAuditResult($auditResult);
        $audit->setAuditComment($comment);
        $audit->setAuditTime(new \DateTime());

        // 更新申请状态
        if ($auditResult === 'approved') {
            $application->setApplicationStatus('approved');
            $audit->setAuditStatus('approved');
        } elseif ($auditResult === 'rejected') {
            $application->setApplicationStatus('rejected');
            $audit->setAuditStatus('rejected');
        }

        $application->setReviewComment($comment);
        $application->setReviewTime(new \DateTime());

        $this->entityManager->persist($audit);
        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $audit;
    }

    /**
     * 发放证书
     * 
     * @param string $applicationId 申请ID
     * @return Certificate 发放的证书
     */
    public function issueCertificate(string $applicationId): Certificate
    {
        $application = $this->applicationRepository->find($applicationId);
        if (!$application) {
            throw new \InvalidArgumentException('证书申请不存在');
        }

        if ($application->getApplicationStatus() !== 'approved') {
            throw new \InvalidArgumentException('申请未通过审核，无法发放证书');
        }

        // 检查是否已经发放过证书
        $existingCertificate = $this->certificateRepository->findOneBy([
            'user' => $application->getUser(),
            'title' => $application->getTemplate()->getTemplateName(),
        ]);

        if ((bool) $existingCertificate) {
            throw new \InvalidArgumentException('该用户已拥有此类型证书');
        }

        // 生成证书
        $certificate = $this->generatorService->generateSingleCertificate(
            $application->getUser()->getUserIdentifier(),
            $application->getTemplate()->getId(),
            $application->getApplicationData()
        );

        // 更新申请状态
        $application->setApplicationStatus('issued');

        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $certificate;
    }

    /**
     * 验证申请数据
     * 
     * @param array $applicationData 申请数据
     * @throws \InvalidArgumentException
     */
    private function validateApplicationData(array $applicationData): void
    {
        $requiredFields = ['templateId', 'type'];
        
        foreach ($requiredFields as $field) {
            if (!isset($applicationData[$field]) || empty($applicationData[$field])) {
                throw new \InvalidArgumentException("缺少必需字段: {$field}");
            }
        }

        $validTypes = ['standard', 'renewal', 'upgrade'];
        if (!in_array($applicationData['type'], $validTypes)) {
            throw new \InvalidArgumentException('无效的申请类型');
        }
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
     * 获取用户的证书列表
     * 
     * @param UserInterface $user 用户对象
     * @return Certificate[] 证书列表
     */
    public function getUserCertificates(UserInterface $user): array
    {
        return $this->certificateRepository->findBy(['user' => $user]);
    }

    /**
     * 获取用户的申请列表
     * 
     * @param UserInterface $user 用户对象
     * @return CertificateApplication[] 申请列表
     */
    public function getUserApplications(UserInterface $user): array
    {
        return $this->applicationRepository->findBy(['user' => $user]);
    }

    /**
     * 检查证书是否有效
     * 
     * @param Certificate $certificate 证书对象
     * @return bool 是否有效
     */
    public function isCertificateValid(Certificate $certificate): bool
    {
        return $certificate->isValid() === true;
    }
} 