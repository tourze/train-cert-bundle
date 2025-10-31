<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateAudit;

class CertificateAuditFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 获取已存在的证书申请
        $applications = $manager->getRepository(CertificateApplication::class)->findAll();

        if ([] === $applications) {
            return; // 如果没有申请，跳过审核记录创建
        }

        $auditStatuses = ['pending', 'in_progress', 'approved', 'rejected', 'completed'];
        $auditResults = ['approved', 'rejected', 'require_documents'];

        for ($i = 1; $i <= 3; ++$i) {
            $audit = new CertificateAudit();

            // 关联申请
            $audit->setApplication($applications[array_rand($applications)]);

            // 设置审核状态和结果
            $audit->setAuditStatus($auditStatuses[array_rand($auditStatuses)]);
            $audit->setAuditResult($auditResults[array_rand($auditResults)]);

            // 设置审核意见
            $audit->setAuditComment("Audit comment for record {$i}");

            // 设置审核人
            $audit->setAuditor("Auditor {$i}");

            // 设置审核时间
            $audit->setAuditTime(new \DateTimeImmutable("-{$i} days"));

            // 设置审核详情
            $audit->setAuditDetails([
                'review_score' => rand(60, 100),
                'review_notes' => "Review notes for audit {$i}",
                'documents_checked' => true,
            ]);

            $manager->persist($audit);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CertificateApplicationFixtures::class,
        ];
    }
}
