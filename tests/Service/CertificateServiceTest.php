<?php

namespace Tourze\TrainCertBundle\Tests\Service;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\JsonRPCLockBundle\JsonRPCLockBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateAudit;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;
use Tourze\TrainCertBundle\Service\CertificateService;
use Tourze\TrainCertBundle\TrainCertBundle;

/**
 * CertificateService集成测试
 * 测试证书服务的核心业务逻辑，包括证书申请、审核、发放等完整流程
 *
 * @internal
 */
#[CoversClass(CertificateService::class)]
#[RunTestsInSeparateProcesses]
final class CertificateServiceTest extends AbstractIntegrationTestCase
{
    private CertificateService $certificateService;

    /**
     * @return array<class-string, array<string, bool>>
     */
    public static function configureBundles(): array
    {
        return [
            FrameworkBundle::class => ['all' => true],
            SecurityBundle::class => ['all' => true],
            DoctrineBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            JsonRPCLockBundle::class => ['all' => true],
            TrainCertBundle::class => ['all' => true],
        ];
    }

    protected function onSetUp(): void
    {
        // 从服务容器获取 CertificateService
        $this->certificateService = self::getService(CertificateService::class);
    }

    public function testGenerateCertificateWithValidData(): void
    {
        // 创建测试用户
        $testUser = $this->createNormalUser('test-user-001@test.com', 'test123');

        // 创建测试模板
        $template = $this->createActiveTemplate('培训证书', 'training');
        $templateId = (string) $template->getId();

        // 生成证书
        $certificate = $this->certificateService->generateCertificate(
            $testUser->getUserIdentifier(),
            'course-001',
            $templateId
        );

        // 验证证书创建成功
        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertEquals('培训证书', $certificate->getTitle());
        $this->assertTrue($certificate->isValid());
        $this->assertNotNull($certificate->getImgUrl());
    }

    public function testGenerateCertificateWithNonExistentTemplate(): void
    {
        // 创建测试用户
        $testUser = $this->createNormalUser('test-user-001@test.com', 'test123');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        $this->certificateService->generateCertificate(
            $testUser->getUserIdentifier(),
            'course-001',
            'non-existent-template-id'
        );
    }

    public function testGenerateCertificateWithInactiveTemplate(): void
    {
        // 创建测试用户
        $testUser = $this->createNormalUser('test-user-001@test.com', 'test123');

        // 创建非活跃模板
        $template = $this->createInactiveTemplate('停用证书', 'disabled');
        $templateId = (string) $template->getId();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板未启用');

        $this->certificateService->generateCertificate(
            $testUser->getUserIdentifier(),
            'course-001',
            $templateId
        );
    }

    public function testApplyCertificateWithValidData(): void
    {
        // 创建测试模板
        $template = $this->createActiveTemplate('专业证书', 'professional');

        $applicationData = [
            'templateId' => (string) $template->getId(),
            'type' => 'standard',
            'requiredDocuments' => ['身份证', '培训记录'],
        ];

        // 创建测试用户
        $testUser = $this->createNormalUser('test-user-002@test.com', 'test123');

        // 申请证书
        $application = $this->certificateService->applyCertificate(
            $testUser->getUserIdentifier(),
            'course-002',
            $applicationData
        );

        // 验证申请创建成功
        $this->assertInstanceOf(CertificateApplication::class, $application);
        $this->assertEquals($template->getId(), $application->getTemplate()->getId());
        $this->assertEquals('standard', $application->getApplicationType());
        $this->assertEquals('pending', $application->getApplicationStatus());
        $this->assertNotNull($application->getApplicationTime());
        $this->assertEquals(['身份证', '培训记录'], $application->getRequiredDocuments());
    }

    public function testApplyCertificateWithMissingRequiredFields(): void
    {
        $applicationData = [
            'type' => 'standard', // 缺少 templateId
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('缺少必需字段: templateId');

        // 创建测试用户
        $testUser = $this->createNormalUser('test-user-003@test.com', 'test123');

        $this->certificateService->applyCertificate(
            $testUser->getUserIdentifier(),
            'course-003',
            $applicationData
        );
    }

    public function testApplyCertificateWithInvalidType(): void
    {
        $template = $this->createActiveTemplate('技能证书', 'skill');

        $applicationData = [
            'templateId' => (string) $template->getId(),
            'type' => 'invalid-type', // 无效类型
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的申请类型');

        // 创建测试用户
        $testUser = $this->createNormalUser('test-user-004@test.com', 'test123');

        $this->certificateService->applyCertificate(
            $testUser->getUserIdentifier(),
            'course-004',
            $applicationData
        );
    }

    public function testApplyCertificateWithNonExistentTemplate(): void
    {
        $applicationData = [
            'templateId' => 'non-existent-id',
            'type' => 'standard',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        // 创建测试用户
        $testUser = $this->createNormalUser('test-user-005@test.com', 'test123');

        $this->certificateService->applyCertificate(
            $testUser->getUserIdentifier(),
            'course-005',
            $applicationData
        );
    }

    public function testAuditCertificateApproval(): void
    {
        // 创建申请
        $application = $this->createPendingApplication();
        $applicationId = (string) $application->getId();

        // 审核通过
        $audit = $this->certificateService->auditCertificate(
            $applicationId,
            'approved',
            '申请材料完整，符合要求'
        );

        // 验证审核结果
        $this->assertInstanceOf(CertificateAudit::class, $audit);
        $this->assertEquals('approved', $audit->getAuditResult());
        $this->assertEquals('申请材料完整，符合要求', $audit->getAuditComment());
        $this->assertEquals('approved', $audit->getAuditStatus());
        $this->assertTrue($audit->isApproved());

        // 验证申请状态更新
        self::getEntityManager()->refresh($application);
        $this->assertEquals('approved', $application->getApplicationStatus());
        $this->assertEquals('申请材料完整，符合要求', $application->getReviewComment());
        $this->assertNotNull($application->getReviewTime());
    }

    public function testAuditCertificateRejection(): void
    {
        // 创建申请
        $application = $this->createPendingApplication();
        $applicationId = (string) $application->getId();

        // 审核拒绝
        $audit = $this->certificateService->auditCertificate(
            $applicationId,
            'rejected',
            '材料不完整，需要补充'
        );

        // 验证审核结果
        $this->assertInstanceOf(CertificateAudit::class, $audit);
        $this->assertEquals('rejected', $audit->getAuditResult());
        $this->assertEquals('材料不完整，需要补充', $audit->getAuditComment());
        $this->assertEquals('rejected', $audit->getAuditStatus());
        $this->assertFalse($audit->isApproved());

        // 验证申请状态更新
        self::getEntityManager()->refresh($application);
        $this->assertEquals('rejected', $application->getApplicationStatus());
    }

    public function testAuditNonExistentApplication(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书申请不存在');

        $this->certificateService->auditCertificate(
            'non-existent-id',
            'approved',
            '审核意见'
        );
    }

    public function testAuditNonPendingApplication(): void
    {
        // 创建已审核的申请
        $application = $this->createPendingApplication();
        $application->setApplicationStatus('approved');
        self::getEntityManager()->flush();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('申请状态不允许审核');

        $this->certificateService->auditCertificate(
            (string) $application->getId(),
            'rejected',
            '重复审核'
        );
    }

    public function testIssueCertificateSuccess(): void
    {
        // 创建已通过审核的申请
        $application = $this->createApprovedApplication();
        $applicationId = (string) $application->getId();

        // 发放证书
        $certificate = $this->certificateService->issueCertificate($applicationId);

        // 验证证书创建
        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertEquals($application->getTemplate()->getTemplateName(), $certificate->getTitle());
        $this->assertTrue($certificate->isValid());

        // 验证申请状态更新
        self::getEntityManager()->refresh($application);
        $this->assertEquals('issued', $application->getApplicationStatus());
    }

    public function testIssueCertificateForNonExistentApplication(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书申请不存在');

        $this->certificateService->issueCertificate('non-existent-id');
    }

    public function testIssueCertificateForNonApprovedApplication(): void
    {
        // 创建待审核申请
        $application = $this->createPendingApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('申请未通过审核，无法发放证书');

        $this->certificateService->issueCertificate((string) $application->getId());
    }

    public function testIssueDuplicateCertificate(): void
    {
        // 创建用户和已有证书
        $user = $this->createNormalUser('duplicate-user@test.com', 'test123');
        $template = $this->createActiveTemplate('重复证书', 'duplicate');

        // 创建已存在的证书
        $existingCertificate = new Certificate();
        $existingCertificate->setUser($user);
        $existingCertificate->setTitle($template->getTemplateName());
        $existingCertificate->setValid(true);
        self::getEntityManager()->persist($existingCertificate);

        // 创建通过审核的申请
        $application = $this->createApprovedApplication($user, $template);

        self::getEntityManager()->flush();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('该用户已拥有此类型证书');

        $this->certificateService->issueCertificate((string) $application->getId());
    }

    public function testGetUserCertificates(): void
    {
        $user = $this->createNormalUser('cert-user@test.com', 'test123');

        // 创建用户证书
        $certificate1 = $this->createCertificateForUser($user, '证书1');
        $certificate2 = $this->createCertificateForUser($user, '证书2');

        // 获取用户证书列表
        $certificates = $this->certificateService->getUserCertificates($user);

        $this->assertCount(2, $certificates);
        $this->assertContains($certificate1, $certificates);
        $this->assertContains($certificate2, $certificates);
    }

    public function testGetUserApplications(): void
    {
        $user = $this->createNormalUser('app-user@test.com', 'test123');

        // 创建用户申请
        $application1 = $this->createApplicationForUser($user, 'standard');
        $application2 = $this->createApplicationForUser($user, 'renewal');

        // 获取用户申请列表
        $applications = $this->certificateService->getUserApplications($user);

        $this->assertCount(2, $applications);
        $this->assertContains($application1, $applications);
        $this->assertContains($application2, $applications);
    }

    public function testIsCertificateValid(): void
    {
        $validCertificate = new Certificate();
        $validCertificate->setValid(true);

        $invalidCertificate = new Certificate();
        $invalidCertificate->setValid(false);

        $this->assertTrue($this->certificateService->isCertificateValid($validCertificate));
        $this->assertFalse($this->certificateService->isCertificateValid($invalidCertificate));
    }

    /**
     * 创建活跃的证书模板
     */
    private function createActiveTemplate(string $name, string $type): CertificateTemplate
    {
        $template = new CertificateTemplate();
        $template->setTemplateName($name);
        $template->setTemplateType($type);
        $template->setIsActive(true);
        $template->setDescription('测试模板');
        $template->setTemplateConfig(['validityPeriod' => 365]);

        self::getEntityManager()->persist($template);
        self::getEntityManager()->flush();

        return $template;
    }

    /**
     * 创建非活跃的证书模板
     */
    private function createInactiveTemplate(string $name, string $type): CertificateTemplate
    {
        $template = new CertificateTemplate();
        $template->setTemplateName($name);
        $template->setTemplateType($type);
        $template->setIsActive(false);
        $template->setDescription('停用模板');

        self::getEntityManager()->persist($template);
        self::getEntityManager()->flush();

        return $template;
    }

    /**
     * 创建待审核申请
     */
    private function createPendingApplication(?UserInterface $user = null, ?CertificateTemplate $template = null): CertificateApplication
    {
        $user ??= $this->createNormalUser('pending-user-' . uniqid() . '@test.com', 'test123');
        $template ??= $this->createActiveTemplate('审核证书', 'pending');

        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType('standard');
        $application->setApplicationStatus('pending');
        $application->setApplicationTime(new \DateTimeImmutable());
        $application->setApplicationData([
            'templateId' => (string) $template->getId(),
            'type' => 'standard',
        ]);

        self::getEntityManager()->persist($application);
        self::getEntityManager()->flush();

        return $application;
    }

    /**
     * 创建已通过审核的申请
     */
    private function createApprovedApplication(?UserInterface $user = null, ?CertificateTemplate $template = null): CertificateApplication
    {
        $user ??= $this->createNormalUser('approved-user-' . uniqid() . '@test.com', 'test123');
        $template ??= $this->createActiveTemplate('批准证书', 'approved');

        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType('standard');
        $application->setApplicationStatus('approved');
        $application->setApplicationTime(new \DateTimeImmutable());
        $application->setReviewTime(new \DateTimeImmutable());
        $application->setApplicationData([
            'templateId' => (string) $template->getId(),
            'type' => 'standard',
        ]);

        self::getEntityManager()->persist($application);
        self::getEntityManager()->flush();

        return $application;
    }

    /**
     * 为用户创建证书
     */
    private function createCertificateForUser(UserInterface $user, string $title): Certificate
    {
        $certificate = new Certificate();
        $certificate->setUser($user);
        $certificate->setTitle($title);
        $certificate->setValid(true);

        self::getEntityManager()->persist($certificate);
        self::getEntityManager()->flush();

        return $certificate;
    }

    /**
     * 为用户创建申请
     */
    private function createApplicationForUser(UserInterface $user, string $type): CertificateApplication
    {
        $template = $this->createActiveTemplate("用户证书-{$type}", $type);

        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType($type);
        $application->setApplicationStatus('pending');
        $application->setApplicationTime(new \DateTimeImmutable());

        self::getEntityManager()->persist($application);
        self::getEntityManager()->flush();

        return $application;
    }
}
