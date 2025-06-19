<?php

namespace Tourze\TrainCertBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;

/**
 * 证书工作流程集成测试
 * 测试从申请到发放的完整流程
 */
class CertificateWorkflowTest extends TestCase
{
    public function testCertificateWorkflow(): void
    {
        // 1. 创建证书模板
        $template = new CertificateTemplate();
        $template->setTemplateName('安全生产培训证书');
        $template->setTemplateType('safety');
        $template->setTemplatePath('/templates/safety.pdf');
        $template->setTemplateConfig(['validityPeriod' => 365]);
        $template->setFieldMapping(['userName' => 'holder_name']);
        $template->setIsDefault(true);
        $template->setIsActive(true);

        $this->assertInstanceOf(CertificateTemplate::class, $template);
        $this->assertTrue($template->isActive());
        $this->assertTrue($template->isDefault());

        // 2. 创建证书申请
        $application = new CertificateApplication();
        $application->setTemplate($template);
        $application->setApplicationType('standard');
        $application->setApplicationStatus('pending');
        $application->setApplicationData(['userName' => '张三', 'idCard' => '123456789']);
        $application->setRequiredDocuments(['身份证', '培训证明']);
        $application->setApplicationTime(new \DateTimeImmutable());

        $this->assertInstanceOf(CertificateApplication::class, $application);
        $this->assertEquals('pending', $application->getApplicationStatus());
        $this->assertEquals($template, $application->getTemplate());

        // 3. 模拟审核通过
        $application->setApplicationStatus('approved');
        $application->setReviewComment('审核通过');
        $application->setReviewTime(new \DateTimeImmutable());

        $this->assertEquals('approved', $application->getApplicationStatus());
        $this->assertEquals('审核通过', $application->getReviewComment());

        // 4. 创建证书
        $certificate = new Certificate();
        $certificate->setTitle($template->getTemplateName());
        $certificate->setImgUrl('/certificates/cert_123.pdf');
        $certificate->setValid(true);

        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertTrue($certificate->isValid());
        $this->assertEquals('安全生产培训证书', $certificate->getTitle());

        // 5. 更新申请状态为已发放
        $application->setApplicationStatus('issued');

        $this->assertEquals('issued', $application->getApplicationStatus());
    }

    public function testCertificateApplicationRejection(): void
    {
        // 测试申请被拒绝的流程
        $template = new CertificateTemplate();
        $template->setTemplateName('技能培训证书');
        $template->setTemplateType('skill');
        $template->setIsActive(true);

        $application = new CertificateApplication();
        $application->setTemplate($template);
        $application->setApplicationType('standard');
        $application->setApplicationStatus('pending');
        $application->setApplicationData(['userName' => '李四']);
        $application->setApplicationTime(new \DateTimeImmutable());

        // 模拟审核拒绝
        $application->setApplicationStatus('rejected');
        $application->setReviewComment('材料不齐全');
        $application->setReviewTime(new \DateTimeImmutable());

        $this->assertEquals('rejected', $application->getApplicationStatus());
        $this->assertEquals('材料不齐全', $application->getReviewComment());
    }

    public function testTemplateActivationDeactivation(): void
    {
        // 测试模板启用/禁用功能
        $template = new CertificateTemplate();
        $template->setTemplateName('管理培训证书');
        $template->setTemplateType('management');
        $template->setIsActive(true);

        $this->assertTrue($template->isActive());

        // 禁用模板
        $template->setIsActive(false);
        $this->assertFalse($template->isActive());

        // 重新启用
        $template->setIsActive(true);
        $this->assertTrue($template->isActive());
    }

    public function testDefaultTemplateManagement(): void
    {
        // 测试默认模板管理
        $template1 = new CertificateTemplate();
        $template1->setTemplateName('安全模板1');
        $template1->setTemplateType('safety');
        $template1->setIsDefault(true);

        $template2 = new CertificateTemplate();
        $template2->setTemplateName('安全模板2');
        $template2->setTemplateType('safety');
        $template2->setIsDefault(false);

        $this->assertTrue($template1->isDefault());
        $this->assertFalse($template2->isDefault());

        // 模拟设置新的默认模板（实际应用中需要取消其他默认模板）
        $template1->setIsDefault(false);
        $template2->setIsDefault(true);

        $this->assertFalse($template1->isDefault());
        $this->assertTrue($template2->isDefault());
    }

    public function testCertificateValidation(): void
    {
        // 测试证书验证功能
        $certificate = new Certificate();
        $certificate->setTitle('测试证书');
        $certificate->setImgUrl('/certificates/test.pdf');
        $certificate->setValid(true);

        $this->assertTrue($certificate->isValid());

        // 撤销证书
        $certificate->setValid(false);
        $this->assertFalse($certificate->isValid());
    }

    public function testApplicationDataValidation(): void
    {
        // 测试申请数据验证
        $application = new CertificateApplication();
        $application->setApplicationType('standard');
        $application->setApplicationStatus('pending');

        // 测试有效的申请类型
        $validTypes = ['standard', 'renewal', 'upgrade'];
        foreach ($validTypes as $type) {
            $application->setApplicationType($type);
            $this->assertEquals($type, $application->getApplicationType());
        }

        // 测试有效的申请状态
        $validStatuses = ['pending', 'approved', 'rejected', 'issued'];
        foreach ($validStatuses as $status) {
            $application->setApplicationStatus($status);
            $this->assertEquals($status, $application->getApplicationStatus());
        }
    }
} 