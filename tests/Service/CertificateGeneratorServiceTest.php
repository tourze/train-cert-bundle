<?php

namespace Tourze\TrainCertBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;
use Tourze\TrainCertBundle\Service\CertificateGeneratorService;

/**
 * CertificateGeneratorService 测试类
 *
 * 测试证书生成服务的核心功能：
 * - 单个证书生成
 * - 批量证书生成
 * - 验证码生成
 * - 证书预览
 * - 数据验证
 *
 * @internal
 */
#[CoversClass(CertificateGeneratorService::class)]
#[RunTestsInSeparateProcesses]
final class CertificateGeneratorServiceTest extends AbstractIntegrationTestCase
{
    private CertificateGeneratorService $service;

    private UserInterface $testUser;

    protected function onSetUp(): void
    {
        $this->service = self::getService(CertificateGeneratorService::class);
        $this->testUser = $this->createNormalUser('test@example.com', 'test123');
        self::getEntityManager()->persist($this->testUser);
        self::getEntityManager()->flush();

        // 清理测试数据
        $this->clearTestData();
    }

    public function testServiceIsAccessible(): void
    {
        $this->assertInstanceOf(CertificateGeneratorService::class, $this->service);
    }

    public function testGenerateVerificationCode(): void
    {
        $verificationCode = $this->service->generateVerificationCode('cert-123');

        $this->assertIsString($verificationCode);
        $this->assertEquals(12, strlen($verificationCode));
        $this->assertEquals(strtoupper($verificationCode), $verificationCode);
    }

    public function testGenerateVerificationCodesAreUnique(): void
    {
        $code1 = $this->service->generateVerificationCode('cert-1');
        $code2 = $this->service->generateVerificationCode('cert-2');

        $this->assertNotEquals($code1, $code2);
    }

    public function testGenerateVerificationCodeConsistency(): void
    {
        $code1 = $this->service->generateVerificationCode('cert-123');
        $code2 = $this->service->generateVerificationCode('cert-123');

        $this->assertEquals($code1, $code2);
    }

    public function testGenerateSingleCertificateSuccess(): void
    {
        // 创建活跃的证书模板
        $template = $this->createActiveTemplate();

        // 生成证书
        $certificateData = [
            'issuingAuthority' => '测试机构',
            'courseName' => '软件开发培训',
            'completionDate' => '2024-01-01',
        ];

        $certificate = $this->service->generateSingleCertificate(
            $this->testUser->getUserIdentifier(),
            (string) $template->getId(),
            $certificateData
        );

        // 验证证书属性
        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertSame($this->testUser, $certificate->getUser());
        $this->assertSame($template->getTemplateName(), $certificate->getTitle());
        $this->assertTrue($certificate->isValid());
        $this->assertNotEmpty($certificate->getImgUrl());
        $imgUrl = $certificate->getImgUrl();
        $this->assertNotNull($imgUrl, 'imgUrl should not be null after certificate generation');
        $this->assertStringStartsWith('/certificates/', $imgUrl);
    }

    public function testGenerateSingleCertificateWithNonExistentTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        $this->service->generateSingleCertificate(
            $this->testUser->getUserIdentifier(),
            'non-existent-template-id',
            []
        );
    }

    public function testGenerateSingleCertificateWithInactiveTemplate(): void
    {
        // 创建非活跃模板
        $template = $this->createInactiveTemplate();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板未启用');

        $this->service->generateSingleCertificate(
            $this->testUser->getUserIdentifier(),
            (string) $template->getId(),
            []
        );
    }

    public function testGenerateSingleCertificateWithNonExistentUser(): void
    {
        $template = $this->createActiveTemplate();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('用户不存在: invalid-user-id');

        $this->service->generateSingleCertificate(
            'invalid-user-id',
            (string) $template->getId(),
            []
        );
    }

    public function testGenerateBatchCertificatesSuccess(): void
    {
        // 创建多个用户
        $user1 = $this->createNormalUser('user1@example.com', 'pass123');
        $user2 = $this->createNormalUser('user2@example.com', 'pass123');
        self::getEntityManager()->persist($user1);
        self::getEntityManager()->persist($user2);
        self::getEntityManager()->flush();

        // 创建模板
        $template = $this->createActiveTemplate();

        // 批量生成证书
        $userIds = [$user1->getUserIdentifier(), $user2->getUserIdentifier()];
        $config = ['issuingAuthority' => '批量测试机构'];

        $certificates = $this->service->generateBatchCertificates(
            $userIds,
            (string) $template->getId(),
            $config
        );

        // 验证结果
        $this->assertCount(2, $certificates);
        $this->assertContainsOnlyInstancesOf(Certificate::class, $certificates);

        // 验证每个证书
        foreach ($certificates as $certificate) {
            $this->assertTrue($certificate->isValid());
            $this->assertSame($template->getTemplateName(), $certificate->getTitle());
        }
    }

    public function testGenerateBatchCertificatesWithSomeInvalidUsers(): void
    {
        $template = $this->createActiveTemplate();

        // 包含有效和无效用户ID
        $userIds = [
            $this->testUser->getUserIdentifier(),
            'invalid-user-1',
            'invalid-user-2',
        ];

        $certificates = $this->service->generateBatchCertificates(
            $userIds,
            (string) $template->getId(),
            []
        );

        // 只有一个有效用户应该成功生成证书
        $this->assertCount(1, $certificates);
        $this->assertSame($this->testUser, $certificates[0]->getUser());
    }

    public function testPreviewCertificateSuccess(): void
    {
        $template = $this->createActiveTemplate();

        $sampleData = [
            'userName' => '张三',
            'courseName' => '测试课程',
            'completionDate' => '2024-01-01',
        ];

        $previewUrl = $this->service->previewCertificate(
            (string) $template->getId(),
            $sampleData
        );

        $this->assertIsString($previewUrl);
        $this->assertStringStartsWith('/certificates/', $previewUrl);
        $this->assertStringContainsString('.pdf', $previewUrl);
    }

    public function testPreviewCertificateWithNonExistentTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        $this->service->previewCertificate('non-existent-template', []);
    }

    public function testValidateCertificateDataSuccess(): void
    {
        $validData = [
            'userId' => 'user123',
            'templateId' => 'template456',
            'extraField' => 'extraValue',
        ];

        $result = $this->service->validateCertificateData($validData);
        $this->assertTrue($result);
    }

    public function testValidateCertificateDataMissingUserId(): void
    {
        $invalidData = [
            'templateId' => 'template456',
        ];

        $result = $this->service->validateCertificateData($invalidData);
        $this->assertFalse($result);
    }

    public function testValidateCertificateDataMissingTemplateId(): void
    {
        $invalidData = [
            'userId' => 'user123',
        ];

        $result = $this->service->validateCertificateData($invalidData);
        $this->assertFalse($result);
    }

    public function testValidateCertificateDataEmptyUserId(): void
    {
        $invalidData = [
            'userId' => '',
            'templateId' => 'template456',
        ];

        $result = $this->service->validateCertificateData($invalidData);
        $this->assertFalse($result);
    }

    public function testValidateCertificateDataEmptyTemplateId(): void
    {
        $invalidData = [
            'userId' => 'user123',
            'templateId' => '',
        ];

        $result = $this->service->validateCertificateData($invalidData);
        $this->assertFalse($result);
    }

    /**
     * 创建活跃的证书模板
     */
    private function createActiveTemplate(): CertificateTemplate
    {
        $template = new CertificateTemplate();
        $template->setTemplateName('测试证书模板');
        $template->setTemplateType('training');
        $template->setTemplatePath('/templates/test.html');
        $template->setTemplateConfig(['validityPeriod' => 365]);
        $template->setFieldMapping(['name' => 'userName', 'course' => 'courseName']);
        $template->setIsDefault(false);
        $template->setIsActive(true);

        self::getEntityManager()->persist($template);
        self::getEntityManager()->flush();

        return $template;
    }

    /**
     * 创建非活跃的证书模板
     */
    private function createInactiveTemplate(): CertificateTemplate
    {
        $template = new CertificateTemplate();
        $template->setTemplateName('非活跃测试模板');
        $template->setTemplateType('training');
        $template->setTemplatePath('/templates/inactive.html');
        $template->setTemplateConfig([]);
        $template->setFieldMapping([]);
        $template->setIsDefault(false);
        $template->setIsActive(false); // 设为非活跃

        self::getEntityManager()->persist($template);
        self::getEntityManager()->flush();

        return $template;
    }

    /**
     * 清理测试数据
     */
    private function clearTestData(): void
    {
        $connection = self::getEntityManager()->getConnection();
        $connection->executeStatement('DELETE FROM job_training_certificate_record');
        $connection->executeStatement('DELETE FROM job_training_certificate');
        $connection->executeStatement('DELETE FROM job_training_certificate_template');
    }
}
