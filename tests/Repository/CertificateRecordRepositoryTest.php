<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateRecord;
use Tourze\TrainCertBundle\Repository\CertificateRecordRepository;

/**
 * @template-extends AbstractRepositoryTestCase<CertificateRecord>
 * @internal
 */
#[CoversClass(CertificateRecordRepository::class)]
#[RunTestsInSeparateProcesses]
final class CertificateRecordRepositoryTest extends AbstractRepositoryTestCase
{
    private CertificateRecordRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(CertificateRecordRepository::class);

        // 确保 DataFixtures 已加载
        $this->ensureDataFixturesLoaded();
    }

    /**
     * 确保 DataFixtures 已加载
     */
    private function ensureDataFixturesLoaded(): void
    {
        // 检查是否已有数据
        if ($this->repository->count() > 0) {
            return;
        }

        // 如果没有数据，手动创建一些测试数据
        $manager = self::getEntityManager();

        // 创建用户
        $user = $this->createNormalUser('test@example.com', 'password');
        $manager->persist($user);

        // 创建证书
        $certificate = new Certificate();
        $certificate->setTitle('Test Certificate');
        $certificate->setUser($user);
        $certificate->setValid(true);
        $manager->persist($certificate);

        // 创建证书记录
        $record = new CertificateRecord();
        $record->setCertificate($certificate);
        $record->setCertificateNumber('CERT-FIXTURE-' . uniqid());
        $record->setVerificationCode('VERIFY-FIXTURE-' . uniqid());
        $record->setCertificateType('training');
        $record->setIssuingAuthority('Test Authority');
        $record->setIssueDate(new \DateTimeImmutable());
        $record->setExpiryDate(new \DateTimeImmutable('+1 year'));
        $manager->persist($record);

        $manager->flush();
    }

    public function testFindWithEmptyStringIdShouldReturnNull(): void
    {
        $result = $this->repository->find('');
        $this->assertNull($result);
    }

    public function testSaveWithoutFlushShouldNotPersistEntity(): void
    {
        $record = $this->createCertificateRecord();

        $this->repository->save($record, false);

        // 清空实体管理器来模拟新的事务
        $em = self::getEntityManager();
        $em->clear();

        $result = $this->repository->find($record->getId());
        $this->assertNull($result);
    }

    public function testRemoveWithFlushShouldDeleteEntity(): void
    {
        $record = $this->createCertificateRecord();
        $this->repository->save($record);

        $id = $record->getId();
        $this->repository->remove($record, true);

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithoutFlushShouldNotDeleteEntity(): void
    {
        $record = $this->createCertificateRecord();
        $this->repository->save($record);

        $id = $record->getId();
        $this->repository->remove($record, false);

        $result = $this->repository->find($id);
        $this->assertInstanceOf(CertificateRecord::class, $result);
    }

    public function testFindByCertificateNumberShouldReturnRecord(): void
    {
        $record = $this->createCertificateRecord();
        $this->repository->save($record);

        $result = $this->repository->findByCertificateNumber($record->getCertificateNumber());
        $this->assertInstanceOf(CertificateRecord::class, $result);
        $this->assertEquals($record->getCertificateNumber(), $result->getCertificateNumber());
    }

    public function testFindByCertificateNumberWithNonExistentNumberShouldReturnNull(): void
    {
        $result = $this->repository->findByCertificateNumber('non-existent-number');
        $this->assertNull($result);
    }

    public function testFindByVerificationCodeShouldReturnRecord(): void
    {
        $record = $this->createCertificateRecord();
        $this->repository->save($record);

        $result = $this->repository->findByVerificationCode($record->getVerificationCode());
        $this->assertInstanceOf(CertificateRecord::class, $result);
        $this->assertEquals($record->getVerificationCode(), $result->getVerificationCode());
    }

    public function testFindByVerificationCodeWithNonExistentCodeShouldReturnNull(): void
    {
        $result = $this->repository->findByVerificationCode('non-existent-code');
        $this->assertNull($result);
    }

    public function testFindExpiringCertificatesShouldReturnExpiringRecords(): void
    {
        $record = $this->createCertificateRecord();
        $expiryDate = new \DateTimeImmutable('+15 days');
        $record->setExpiryDate($expiryDate);
        $this->repository->save($record);

        $result = $this->repository->findExpiringCertificates(30);
        $this->assertNotEmpty($result);
    }

    public function testFindExpiredCertificatesShouldReturnExpiredRecords(): void
    {
        $record = $this->createCertificateRecord();
        $expiryDate = new \DateTimeImmutable('-1 day');
        $record->setExpiryDate($expiryDate);
        $this->repository->save($record);

        $result = $this->repository->findExpiredCertificates();
        $this->assertNotEmpty($result);
    }

    public function testFindByCertificateTypeShouldReturnRecordsOfType(): void
    {
        $record = $this->createCertificateRecord();
        $this->repository->save($record);

        $result = $this->repository->findByCertificateType('training');
        $this->assertNotEmpty($result);
        $this->assertEquals('training', $result[0]->getCertificateType());
    }

    public function testFindByIssuingAuthorityShouldReturnRecordsFromAuthority(): void
    {
        $record = $this->createCertificateRecord();
        $this->repository->save($record);

        $result = $this->repository->findByIssuingAuthority('Test Authority');
        $this->assertNotEmpty($result);
        $this->assertEquals('Test Authority', $result[0]->getIssuingAuthority());
    }

    public function testFindExpiredBeforeShouldReturnExpiredRecords(): void
    {
        $record = $this->createCertificateRecord();
        $expiredDate = new \DateTimeImmutable('-2 days');
        $record->setExpiryDate($expiredDate);
        $this->repository->save($record);

        $targetDate = new \DateTimeImmutable('-1 day');
        $result = $this->repository->findExpiredBefore($targetDate);
        $this->assertNotEmpty($result);
        $this->assertTrue($result[0]->getExpiryDate() < $targetDate);
    }

    public function testFindExpiredBeforeWithFutureDateShouldReturnEmptyArray(): void
    {
        // 先清理数据库中的所有过期记录
        $targetDate = new \DateTimeImmutable('-1 day');
        $expiredRecords = $this->repository->findExpiredBefore($targetDate);
        foreach ($expiredRecords as $expiredRecord) {
            $this->repository->remove($expiredRecord, false);
        }
        self::getEntityManager()->flush();

        // 创建一个未来日期的记录
        $record = $this->createCertificateRecord();
        $futureDate = new \DateTimeImmutable('+1 year');
        $record->setExpiryDate($futureDate);
        $this->repository->save($record);

        // 现在查询过去日期的过期记录应该返回空数组
        $result = $this->repository->findExpiredBefore($targetDate);
        $this->assertEmpty($result);
    }

    private function createCertificateRecord(): CertificateRecord
    {
        // 创建用户
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();

        // 创建证书
        $certificate = new Certificate();
        $certificate->setTitle('Test Certificate');
        $certificate->setUser($user);
        $certificate->setValid(true);
        self::getEntityManager()->persist($certificate);

        $record = new CertificateRecord();
        $record->setCertificate($certificate);
        $record->setCertificateNumber('CERT-' . uniqid());
        $record->setVerificationCode('VERIFY-' . uniqid());
        $record->setCertificateType('training');
        $record->setIssuingAuthority('Test Authority');
        $record->setIssueDate(new \DateTimeImmutable());
        $record->setExpiryDate(new \DateTimeImmutable('+1 year'));

        return $record;
    }

    protected function createNewEntity(): object
    {
        // 创建用户
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();

        // 创建证书
        $certificate = new Certificate();
        $certificate->setTitle('Test Certificate');
        $certificate->setUser($user);
        $certificate->setValid(true);
        self::getEntityManager()->persist($certificate);

        // 创建证书记录
        $entity = new CertificateRecord();
        $entity->setCertificate($certificate);
        $entity->setCertificateNumber('CERT-' . uniqid());
        $entity->setVerificationCode('VERIFY-' . uniqid());
        $entity->setCertificateType('training');
        $entity->setIssuingAuthority('Test Authority');
        $entity->setIssueDate(new \DateTimeImmutable());
        $entity->setExpiryDate(new \DateTimeImmutable('+1 year'));

        return $entity;
    }

    /**
     * @return CertificateRecordRepository
     */
    protected function getRepository(): CertificateRecordRepository
    {
        return $this->repository;
    }
}
