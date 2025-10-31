<?php

namespace Tourze\TrainCertBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateRecord;
use Tourze\TrainCertBundle\Entity\CertificateVerification;
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;
use Tourze\TrainCertBundle\Service\CertificateVerificationService;

/**
 * CertificateVerificationService 集成测试
 *
 * 测试证书验证服务的核心功能：
 * - 按证书编号验证
 * - 按验证码验证
 * - 验证记录管理
 * - 批量验证
 * - 验证历史和统计
 *
 * @internal
 */
#[CoversClass(CertificateVerificationService::class)]
#[RunTestsInSeparateProcesses]
final class CertificateVerificationServiceTest extends AbstractIntegrationTestCase
{
    private CertificateVerificationService $service;

    private CertificateVerificationRepository $verificationRepository;

    private UserInterface $testUser;

    protected function onSetUp(): void
    {
        $this->service = self::getService(CertificateVerificationService::class);
        $this->verificationRepository = self::getService(CertificateVerificationRepository::class);

        // 清理DataFixtures创建的数据，确保每个测试在干净的环境中运行
        $this->cleanFixturesData();

        // 在清理后创建测试用户，避免被清理过程删除
        $this->testUser = $this->createNormalUser('test@example.com', 'test123');
        self::getEntityManager()->persist($this->testUser);
        self::getEntityManager()->flush();
    }

    /**
     * 清理DataFixtures创建的验证数据，但不删除证书实体
     */
    private function cleanFixturesData(): void
    {
        $entityManager = self::getEntityManager();

        // 删除所有CertificateVerification记录
        $verificationRepository = $entityManager->getRepository(CertificateVerification::class);
        $verifications = $verificationRepository->findAll();
        foreach ($verifications as $verification) {
            $entityManager->remove($verification);
        }

        // 删除所有CertificateRecord记录
        $recordRepository = $entityManager->getRepository(CertificateRecord::class);
        $records = $recordRepository->findAll();
        foreach ($records as $record) {
            $entityManager->remove($record);
        }

        $entityManager->flush();
        $entityManager->clear();
    }

    protected function onTearDown(): void
    {
        // 清理测试数据，避免DataFixtures数据影响下一个测试
        $entityManager = self::getEntityManager();

        // 删除所有CertificateVerification记录
        $verificationRepository = $entityManager->getRepository(CertificateVerification::class);
        $verifications = $verificationRepository->findAll();
        foreach ($verifications as $verification) {
            $entityManager->remove($verification);
        }

        // 删除所有CertificateRecord记录
        $recordRepository = $entityManager->getRepository(CertificateRecord::class);
        $records = $recordRepository->findAll();
        foreach ($records as $record) {
            $entityManager->remove($record);
        }

        // 删除所有Certificate记录（除了关联到testUser的，因为testUser会被重新清理）
        $certificateRepository = $entityManager->getRepository(Certificate::class);
        $certificates = $certificateRepository->findAll();
        foreach ($certificates as $certificate) {
            $entityManager->remove($certificate);
        }

        $entityManager->flush();
        $entityManager->clear();

        parent::onTearDown();
    }

    public function testVerifyCertificateWithValidNumber(): void
    {
        // 创建有效证书
        $record = $this->createValidCertificateRecord();
        self::getEntityManager()->flush();

        // 执行验证
        $result = $this->service->verifyCertificate($record->getCertificateNumber());

        // 验证结果
        $this->assertTrue($result['valid']);
        $this->assertSame('证书验证通过', $result['message']);
        $this->assertIsArray($result['data']);
        $this->assertSame($record->getCertificateNumber(), $result['data']['certificateNumber']);
        $this->assertSame($record->getCertificateType(), $result['data']['certificateType']);

        // 验证记录已创建
        $verifications = $this->verificationRepository->findAll();
        $this->assertCount(1, $verifications);
    }

    public function testVerifyCertificateWithInvalidNumber(): void
    {
        // 执行验证（不存在的证书编号）
        $result = $this->service->verifyCertificate('INVALID-CERT-001');

        // 验证结果
        $this->assertFalse($result['valid']);
        $this->assertSame('证书不存在', $result['message']);
        $this->assertNull($result['data']);

        // 验证记录仍会创建（记录验证失败）
        $verifications = $this->verificationRepository->findAll();
        $this->assertCount(1, $verifications);
    }

    public function testVerifyCertificateWithExpiredCertificate(): void
    {
        // 创建过期证书
        $record = $this->createExpiredCertificateRecord();
        self::getEntityManager()->flush();

        // 执行验证
        $result = $this->service->verifyCertificate($record->getCertificateNumber());

        // 验证结果
        $this->assertFalse($result['valid']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('证书已过期', $result['message']);
    }

    public function testVerifyCertificateWithRevokedCertificate(): void
    {
        // 创建被撤销的证书
        $record = $this->createRevokedCertificateRecord();
        self::getEntityManager()->flush();

        // 执行验证
        $result = $this->service->verifyCertificate($record->getCertificateNumber());

        // 验证结果
        $this->assertFalse($result['valid']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('证书已被撤销或无效', $result['message']);
    }

    public function testVerifyByVerificationCodeWithValidCode(): void
    {
        // 创建有效证书
        $record = $this->createValidCertificateRecord();
        self::getEntityManager()->flush();

        // 执行验证
        $result = $this->service->verifyByVerificationCode($record->getVerificationCode());

        // 验证结果
        $this->assertTrue($result['valid']);
        $this->assertSame('证书验证通过', $result['message']);

        // 验证记录已创建
        $verifications = $this->verificationRepository->findAll();
        $this->assertCount(1, $verifications);
    }

    public function testVerifyByVerificationCodeWithInvalidCode(): void
    {
        // 执行验证（不存在的验证码）
        $result = $this->service->verifyByVerificationCode('INVALID-CODE-001');

        // 验证结果
        $this->assertFalse($result['valid']);
        $this->assertSame('验证码无效', $result['message']);
        $this->assertNull($result['data']);
    }

    public function testRecordVerificationWithRequest(): void
    {
        // 模拟HTTP请求
        $request = new Request();
        $request->headers->set('User-Agent', 'Mozilla/5.0 TestAgent');
        $request->headers->set('Referer', 'https://example.com/verify');
        $request->headers->set('Accept-Language', 'zh-CN,zh;q=0.9');

        $requestStack = self::getService(RequestStack::class);
        $requestStack->push($request);

        $certificate = $this->createValidCertificate();
        self::getEntityManager()->flush();

        // 执行记录验证
        $verificationData = [
            'valid' => true,
            'message' => '验证成功',
            'data' => ['test' => 'data'],
        ];

        $verification = $this->service->recordVerification(
            $certificate,
            'certificate_number',
            $verificationData
        );

        // 验证记录属性
        $this->assertSame($certificate, $verification->getCertificate());
        $this->assertSame('certificate_number', $verification->getVerificationMethod());
        $this->assertTrue($verification->getVerificationResult());
        $this->assertSame($verificationData, $verification->getVerificationDetails());
        $this->assertNotNull($verification->getUserAgent());
        $this->assertStringContainsString('来源: https://example.com/verify', $verification->getVerifierInfo() ?? '');

        // 验证数据库记录
        $verifications = $this->verificationRepository->findAll();
        $this->assertCount(1, $verifications);
    }

    public function testRecordVerificationWithoutRequest(): void
    {
        $certificate = $this->createValidCertificate();
        self::getEntityManager()->flush();

        // 执行记录验证（无HTTP请求）
        $verificationData = ['valid' => false, 'message' => '验证失败'];

        $verification = $this->service->recordVerification(
            null,
            'verification_code',
            $verificationData
        );

        // 验证记录属性
        $this->assertNull($verification->getCertificate());
        $this->assertSame('verification_code', $verification->getVerificationMethod());
        $this->assertFalse($verification->getVerificationResult());
        $this->assertNull($verification->getIpAddress());
        $this->assertNull($verification->getUserAgent());
    }

    public function testBatchVerifyCertificates(): void
    {
        // 创建测试数据
        $record1 = $this->createValidCertificateRecord('CERT-001');
        $record2 = $this->createValidCertificateRecord('CERT-002');
        self::getEntityManager()->flush();

        // 批量验证
        $certificateNumbers = ['CERT-001', 'CERT-002', 'INVALID-003'];
        $results = $this->service->batchVerifyCertificates($certificateNumbers);

        // 验证结果
        $this->assertCount(3, $results);
        $this->assertTrue($results['CERT-001']['valid']);
        $this->assertTrue($results['CERT-002']['valid']);
        $this->assertFalse($results['INVALID-003']['valid']);

        // 验证创建了3条验证记录（每个证书编号对应一条记录）
        $verifications = $this->verificationRepository->findAll();
        $this->assertCount(3, $verifications);
    }

    public function testGetCertificateDetails(): void
    {
        // 创建证书
        $record = $this->createValidCertificateRecord();
        self::getEntityManager()->flush();

        // 获取详细信息
        $details = $this->service->getCertificateDetails($record->getCertificateNumber());

        // 验证详细信息
        $this->assertIsArray($details);
        $this->assertSame($record->getCertificateNumber(), $details['certificateNumber']);
        $this->assertSame($record->getCertificateType(), $details['certificateType']);
        $this->assertSame($this->testUser->getUserIdentifier(), $details['holderName']);
        $this->assertSame($record->getCertificate()->getTitle(), $details['title']);
        $this->assertTrue($details['isValid']);
        $this->assertFalse($details['isExpired']);
    }

    public function testGetCertificateDetailsForNonExistentCertificate(): void
    {
        // 获取不存在证书的详细信息
        $details = $this->service->getCertificateDetails('INVALID-CERT-001');

        $this->assertNull($details);
    }

    public function testGetVerificationHistory(): void
    {
        // 创建证书和验证记录
        $certificate = $this->createValidCertificate();
        self::getEntityManager()->flush();

        // 创建多条验证记录
        $this->service->recordVerification($certificate, 'certificate_number', ['valid' => true]);
        $this->service->recordVerification($certificate, 'verification_code', ['valid' => false]);

        // 获取验证历史
        $history = $this->service->getVerificationHistory((string) $certificate->getId());

        // 验证历史记录
        $this->assertCount(2, $history);
    }

    public function testGetVerificationStatistics(): void
    {
        // 创建证书
        $certificate = $this->createValidCertificate();
        self::getEntityManager()->flush();

        // 创建测试数据：3次成功、2次失败
        $this->service->recordVerification($certificate, 'method1', ['valid' => true]);
        $this->service->recordVerification($certificate, 'method2', ['valid' => true]);
        $this->service->recordVerification($certificate, 'method3', ['valid' => true]);
        $this->service->recordVerification(null, 'method4', ['valid' => false]);
        $this->service->recordVerification(null, 'method5', ['valid' => false]);

        // 获取统计数据
        $stats = $this->service->getVerificationStatistics();

        // 验证统计结果
        $this->assertSame(5, $stats['totalVerifications']);
        $this->assertSame(3, $stats['successfulVerifications']);
        $this->assertSame(2, $stats['failedVerifications']);
        $this->assertSame(60.0, $stats['successRate']);
    }

    public function testGetVerificationStatisticsWithDateRange(): void
    {
        // 创建证书
        $certificate = $this->createValidCertificate();
        self::getEntityManager()->flush();

        // 创建测试数据
        $this->service->recordVerification($certificate, 'method1', ['valid' => true]);

        $yesterday = new \DateTimeImmutable('-1 day');
        $tomorrow = new \DateTimeImmutable('+1 day');

        // 获取昨天到明天的统计
        $stats = $this->service->getVerificationStatistics($yesterday, $tomorrow);
        $this->assertSame(1, $stats['totalVerifications']);

        // 获取明天之后的统计（应为0）
        $future = new \DateTimeImmutable('+2 days');
        $stats = $this->service->getVerificationStatistics($future);
        $this->assertSame(0, $stats['totalVerifications']);
    }

    public function testIsFrequentlyVerified(): void
    {
        // 创建证书
        $certificate = $this->createValidCertificate();
        self::getEntityManager()->flush();

        // 创建多次验证记录（超过默认阈值10次）
        for ($i = 0; $i < 12; ++$i) {
            $this->service->recordVerification($certificate, "method{$i}", ['valid' => true]);
        }

        // 检查是否频繁验证
        $isFrequent = $this->service->isFrequentlyVerified((string) $certificate->getId());
        $this->assertTrue($isFrequent);

        // 测试自定义阈值
        $isFrequentWithCustomThreshold = $this->service->isFrequentlyVerified(
            (string) $certificate->getId(),
            3600,
            15
        );
        $this->assertFalse($isFrequentWithCustomThreshold);
    }

    public function testCertificateValidationWithWarnings(): void
    {
        // 创建即将过期的证书（20天后过期）
        $record = $this->createCertificateRecordExpiringIn(20);
        self::getEntityManager()->flush();

        // 执行验证
        $result = $this->service->verifyCertificate($record->getCertificateNumber());

        // 验证结果：应该有效但包含警告
        $this->assertTrue($result['valid']);
        $this->assertSame('证书验证通过', $result['message']);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertNotEmpty($result['warnings']);
        $this->assertIsArray($result['warnings']);
        $this->assertArrayHasKey(0, $result['warnings'], 'warnings array should have at least one element');
        $this->assertIsString($result['warnings'][0]);
        $this->assertStringContainsString('天后过期', $result['warnings'][0]);
    }

    /**
     * 创建有效证书
     */
    private function createValidCertificate(): Certificate
    {
        $certificate = new Certificate();
        $certificate->setTitle('软件开发工程师证书');
        $certificate->setUser($this->testUser);
        $certificate->setValid(true);
        $certificate->setImgUrl('https://example.com/cert.jpg');

        self::getEntityManager()->persist($certificate);

        return $certificate;
    }

    /**
     * 创建有效证书记录
     */
    private function createValidCertificateRecord(?string $certNumber = null): CertificateRecord
    {
        $certificate = $this->createValidCertificate();

        $record = new CertificateRecord();
        $record->setCertificate($certificate);
        $record->setCertificateNumber($certNumber ?? 'CERT-' . uniqid());
        $record->setCertificateType('软件开发');
        $record->setIssueDate(new \DateTimeImmutable('-30 days'));
        $record->setExpiryDate(new \DateTimeImmutable('+365 days'));
        $record->setIssuingAuthority('国家职业技能鉴定中心');
        $record->setVerificationCode('VER-' . uniqid());
        $record->setMetadata(['level' => 'senior', 'skills' => ['php', 'mysql']]);

        self::getEntityManager()->persist($record);

        return $record;
    }

    /**
     * 创建过期证书记录
     */
    private function createExpiredCertificateRecord(): CertificateRecord
    {
        $certificate = $this->createValidCertificate();

        $record = new CertificateRecord();
        $record->setCertificate($certificate);
        $record->setCertificateNumber('EXPIRED-' . uniqid());
        $record->setCertificateType('软件开发');
        $record->setIssueDate(new \DateTimeImmutable('-400 days'));
        $record->setExpiryDate(new \DateTimeImmutable('-30 days')); // 30天前过期
        $record->setIssuingAuthority('国家职业技能鉴定中心');
        $record->setVerificationCode('VER-EXPIRED-' . uniqid());

        self::getEntityManager()->persist($record);

        return $record;
    }

    /**
     * 创建被撤销的证书记录
     */
    private function createRevokedCertificateRecord(): CertificateRecord
    {
        $certificate = new Certificate();
        $certificate->setTitle('被撤销的证书');
        $certificate->setUser($this->testUser);
        $certificate->setValid(false); // 设为无效

        self::getEntityManager()->persist($certificate);

        $record = new CertificateRecord();
        $record->setCertificate($certificate);
        $record->setCertificateNumber('REVOKED-' . uniqid());
        $record->setCertificateType('软件开发');
        $record->setIssueDate(new \DateTimeImmutable('-100 days'));
        $record->setExpiryDate(new \DateTimeImmutable('+265 days'));
        $record->setIssuingAuthority('国家职业技能鉴定中心');
        $record->setVerificationCode('VER-REVOKED-' . uniqid());

        self::getEntityManager()->persist($record);

        return $record;
    }

    /**
     * 创建指定天数后过期的证书记录
     */
    private function createCertificateRecordExpiringIn(int $days): CertificateRecord
    {
        $certificate = $this->createValidCertificate();

        $record = new CertificateRecord();
        $record->setCertificate($certificate);
        $record->setCertificateNumber('EXPIRING-' . uniqid());
        $record->setCertificateType('软件开发');
        $record->setIssueDate(new \DateTimeImmutable('-30 days'));
        $record->setExpiryDate(new \DateTimeImmutable("+{$days} days"));
        $record->setIssuingAuthority('国家职业技能鉴定中心');
        $record->setVerificationCode('VER-EXPIRING-' . uniqid());

        self::getEntityManager()->persist($record);

        return $record;
    }
}
