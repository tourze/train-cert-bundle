<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainCertBundle\Entity\CertificateVerification;
use Tourze\TrainCertBundle\Repository\CertificateRepository;

/**
 * 证书验证测试数据
 */
class CertificateVerificationFixtures extends Fixture
{
    public function __construct(
        private readonly CertificateRepository $certificateRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // 获取证书数据
        $certificates = $this->certificateRepository->findAll();

        if ([] === $certificates) {
            return; // 如果没有证书数据，跳过
        }

        // 为每个证书创建验证记录
        foreach ($certificates as $certificate) {
            // 创建成功的验证记录
            $verification1 = new CertificateVerification();
            $verification1->setCertificate($certificate);
            $verification1->setVerificationMethod('certificate_number');
            $verification1->setVerificationResult(true);
            $verification1->setVerifierInfo('系统自动验证');
            $verification1->setIpAddress('127.0.0.1');
            $verification1->setUserAgent('Test Agent');
            $verification1->setVerificationTime(new \DateTimeImmutable('2024-01-01 10:00:00'));
            $verification1->setVerificationDetails([
                'method' => 'certificate_number',
                'verified_at' => '2024-01-01 10:00:00',
                'status' => 'success',
            ]);
            $manager->persist($verification1);

            // 创建失败的验证记录
            $verification2 = new CertificateVerification();
            $verification2->setCertificate($certificate);
            $verification2->setVerificationMethod('verification_code');
            $verification2->setVerificationResult(false);
            $verification2->setVerifierInfo('用户验证');
            $verification2->setIpAddress('192.168.1.100');
            $verification2->setUserAgent('Mozilla/5.0 (Test Browser)');
            $verification2->setVerificationTime(new \DateTimeImmutable('2024-01-02 15:30:00'));
            $verification2->setVerificationDetails([
                'method' => 'verification_code',
                'error' => '验证码错误',
                'attempted_at' => '2024-01-02 15:30:00',
            ]);
            $manager->persist($verification2);
        }

        $manager->flush();
    }
}
