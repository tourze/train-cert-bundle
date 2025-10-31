<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateRecord;

/**
 * 证书记录测试数据
 */
class CertificateRecordFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 获取已有的证书
        $validCert = $this->getReference(CertificateFixtures::CERTIFICATE_VALID, Certificate::class);

        // 创建证书记录
        $record = new CertificateRecord();
        $record->setCertificate($validCert);
        $record->setCertificateNumber('CERT-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT));
        $record->setCertificateType('safety');
        $record->setIssueDate(new \DateTimeImmutable('-30 days'));
        $record->setExpiryDate(new \DateTimeImmutable('+1 year'));
        $record->setIssuingAuthority('国家安全生产监督管理总局');
        $record->setVerificationCode('VER-' . strtoupper(substr(md5((string) random_int(1, 999999)), 0, 8)));

        $manager->persist($record);

        // 创建第二条记录
        $invalidCert = $this->getReference(CertificateFixtures::CERTIFICATE_INVALID, Certificate::class);

        $record2 = new CertificateRecord();
        $record2->setCertificate($invalidCert);
        $record2->setCertificateNumber('CERT-' . date('Ymd', strtotime('-2 years')) . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT));
        $record2->setCertificateType('skill');
        $record2->setIssueDate(new \DateTimeImmutable('-2 years'));
        $record2->setExpiryDate(new \DateTimeImmutable('-6 months'));
        $record2->setIssuingAuthority('人力资源和社会保障部');
        $record2->setVerificationCode('VER-' . strtoupper(substr(md5((string) random_int(1, 999999)), 0, 8)));

        $manager->persist($record2);

        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            CertificateFixtures::class,
        ];
    }
}
