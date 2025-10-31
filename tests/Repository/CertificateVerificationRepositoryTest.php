<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateVerification;
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;

/**
 * @internal
 */
#[CoversClass(CertificateVerificationRepository::class)]
#[RunTestsInSeparateProcesses]
final class CertificateVerificationRepositoryTest extends AbstractRepositoryTestCase
{
    private CertificateVerificationRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(CertificateVerificationRepository::class);

        // 检查当前测试是否需要 DataFixtures 数据
        $currentTest = $this->name();
        if ('testCountWithDataFixtureShouldReturnGreaterThanZero' === $currentTest) {
            // 为 count 测试创建测试数据
            $certificate = new Certificate();
            $certificate->setTitle('Test Certificate');
            $certificate->setUser($this->createNormalUser('test@example.com', 'password'));
            $certificate->setValid(true);
            self::getEntityManager()->persist($certificate);

            $verification = new CertificateVerification();
            $verification->setCertificate($certificate);
            $verification->setVerificationResult(true);
            $verification->setIpAddress('127.0.0.1');
            $verification->setVerificationTime(new \DateTimeImmutable());
            $verification->setVerificationMethod('API');
            self::getEntityManager()->persist($verification);
            self::getEntityManager()->flush();
        }
    }

    public function testFindWithEmptyStringIdShouldReturnNull(): void
    {
        $result = $this->repository->find('');
        $this->assertNull($result);
    }

    public function testSaveWithoutFlushShouldNotPersistEntity(): void
    {
        $verification = $this->createCertificateVerification();

        $this->repository->save($verification, false);
        $id = $verification->getId();
        self::getEntityManager()->clear();

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithFlushShouldDeleteEntity(): void
    {
        $verification = $this->createCertificateVerification();
        $this->repository->save($verification);

        $id = $verification->getId();
        $this->repository->remove($verification, true);

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithoutFlushShouldNotDeleteEntity(): void
    {
        $verification = $this->createCertificateVerification();
        $this->repository->save($verification);

        $id = $verification->getId();
        $this->repository->remove($verification, false);

        $result = $this->repository->find($id);
        $this->assertInstanceOf(CertificateVerification::class, $result);
    }

    public function testFindByCertificateIdShouldReturnVerificationsForCertificate(): void
    {
        $verification = $this->createCertificateVerification();
        $this->repository->save($verification);

        $certificate = $verification->getCertificate();
        $this->assertNotNull($certificate);
        $certificateId = $certificate->getId();
        $this->assertNotNull($certificateId);
        $result = $this->repository->findByCertificateId($certificateId);
        $this->assertNotEmpty($result);
    }

    public function testFindByCertificateIdWithNonExistentIdShouldReturnEmptyArray(): void
    {
        $result = $this->repository->findByCertificateId('999999999999999999');
        $this->assertEmpty($result);
    }

    public function testFindSuccessfulVerificationsShouldReturnSuccessfulVerifications(): void
    {
        $verification = $this->createCertificateVerification();
        $verification->setVerificationResult(true);
        $this->repository->save($verification);

        $result = $this->repository->findSuccessfulVerifications();
        $this->assertNotEmpty($result);
        $this->assertTrue($result[0]->getVerificationResult());
    }

    public function testFindByIpAddressShouldReturnVerificationsFromIp(): void
    {
        $verification = $this->createCertificateVerification();
        $verification->setIpAddress('192.168.1.100');
        $this->repository->save($verification);

        $result = $this->repository->findByIpAddress('192.168.1.100');
        $this->assertNotEmpty($result);
        $this->assertEquals('192.168.1.100', $result[0]->getIpAddress());
    }

    public function testFindByIpAddressWithNonExistentIpShouldReturnEmptyArray(): void
    {
        $result = $this->repository->findByIpAddress('10.0.0.1');
        $this->assertEmpty($result);
    }

    public function testFindVerificationsBeforeDateShouldReturnVerificationsBeforeDate(): void
    {
        $verification = $this->createCertificateVerification();
        $pastDate = new \DateTimeImmutable('-2 days');
        $verification->setVerificationTime($pastDate);
        $this->repository->save($verification);

        $targetDate = new \DateTimeImmutable('-1 day');
        $result = $this->repository->findVerificationsBeforeDate($targetDate);
        $this->assertNotEmpty($result);
        $this->assertTrue($result[0]->getVerificationTime() < $targetDate);
    }

    public function testFindVerificationsBeforeDateWithFutureDateShouldReturnEmptyArray(): void
    {
        // 清理现有的验证记录以确保测试环境干净
        $existingRecords = $this->repository->findAll();
        foreach ($existingRecords as $record) {
            $this->repository->remove($record, false);
        }
        self::getEntityManager()->flush();

        $verification = $this->createCertificateVerification();
        $futureDate = new \DateTimeImmutable('+1 day');
        $verification->setVerificationTime($futureDate);
        $this->repository->save($verification);

        $targetDate = new \DateTimeImmutable('-1 day');
        $result = $this->repository->findVerificationsBeforeDate($targetDate);
        $this->assertEmpty($result);
    }

    public function testCountVerificationsSinceShouldReturnCorrectCount(): void
    {
        $verification1 = $this->createCertificateVerification();
        $pastDate = new \DateTimeImmutable('-2 days');
        $verification1->setVerificationTime($pastDate);
        $this->repository->save($verification1, false);

        $verification2 = $this->createCertificateVerification();
        $recentDate = new \DateTimeImmutable('-1 hour');
        $verification2->setVerificationTime($recentDate);
        // 使用相同的证书
        $verification2->setCertificate($verification1->getCertificate());
        $this->repository->save($verification2, false);

        self::getEntityManager()->flush();

        $certificate = $verification1->getCertificate();
        $this->assertNotNull($certificate);
        $certificateId = $certificate->getId();
        $this->assertNotNull($certificateId);

        $sinceDate = new \DateTimeImmutable('-1 day');
        $count = $this->repository->countVerificationsSince($certificateId, $sinceDate);

        // 应该只计算-1小时的那个验证记录
        $this->assertEquals(1, $count);
    }

    public function testCountVerificationsSinceWithNonExistentCertificateShouldReturnZero(): void
    {
        $sinceDate = new \DateTimeImmutable('-1 day');
        $count = $this->repository->countVerificationsSince('non-existent-id', $sinceDate);
        $this->assertEquals(0, $count);
    }

    private function createCertificateVerification(): CertificateVerification
    {
        $certificate = $this->createCertificate();

        $em = self::getEntityManager();
        $em->persist($certificate);
        $em->flush();

        $verification = new CertificateVerification();
        $verification->setCertificate($certificate);
        $verification->setVerificationResult(true);
        $verification->setIpAddress('127.0.0.1');
        $verification->setVerificationTime(new \DateTimeImmutable());
        // 显式设置时间戳为 DateTimeImmutable 以避免类型错误
        $verification->setCreateTime(new \DateTimeImmutable('2023-01-01 00:00:00'));
        $verification->setUpdateTime(new \DateTimeImmutable('2023-01-01 00:00:00'));

        return $verification;
    }

    private function createCertificate(): Certificate
    {
        $certificate = new Certificate();
        $certificate->setTitle('Test Certificate ' . uniqid());
        $certificate->setUser($this->createNormalUser('test@example.com', 'password'));
        $certificate->setValid(true);

        return $certificate;
    }

    protected function createNewEntity(): object
    {
        $certificate = new Certificate();
        $certificate->setTitle('Test Certificate for Verification ' . uniqid());
        $certificate->setUser($this->createNormalUser('test@example.com', 'password'));
        $certificate->setValid(true);

        $verification = new CertificateVerification();
        $verification->setCertificate($certificate);
        $verification->setVerificationMethod('API');
        $verification->setVerificationResult(true);
        $verification->setIpAddress('127.0.0.1');
        $verification->setVerificationTime(new \DateTimeImmutable());
        // 显式设置时间戳为 DateTimeImmutable 以避免类型错误
        $verification->setCreateTime(new \DateTimeImmutable('2023-01-01 00:00:00'));
        $verification->setUpdateTime(new \DateTimeImmutable('2023-01-01 00:00:00'));

        return $verification;
    }

    /**
     * @return ServiceEntityRepository<CertificateVerification>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
