<?php

namespace Tourze\TrainCertBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;
use Tourze\TrainCertBundle\Service\CertificateTemplateService;

/**
 * 证书模板服务测试
 */
class CertificateTemplateServiceTest extends TestCase
{
    private CertificateTemplateService $service;
    private EntityManagerInterface&MockObject $entityManager;
    private CertificateTemplateRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CertificateTemplateRepository::class);
        
        $this->service = new CertificateTemplateService(
            $this->entityManager,
            $this->repository
        );
    }

    public function testCreateTemplate(): void
    {
        $templateData = [
            'templateName' => '安全生产培训证书',
            'templateType' => 'safety',
            'templatePath' => '/templates/safety.pdf',
            'templateConfig' => ['validityPeriod' => 365],
            'fieldMapping' => ['userName' => 'holder_name'],
            'isDefault' => true,
            'isActive' => true,
        ];

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(CertificateTemplate::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $template = $this->service->createTemplate($templateData);

        $this->assertInstanceOf(CertificateTemplate::class, $template);
        $this->assertEquals('安全生产培训证书', $template->getTemplateName());
        $this->assertEquals('safety', $template->getTemplateType());
        $this->assertEquals('/templates/safety.pdf', $template->getTemplatePath());
        $this->assertEquals(['validityPeriod' => 365], $template->getTemplateConfig());
        $this->assertEquals(['userName' => 'holder_name'], $template->getFieldMapping());
        $this->assertTrue($template->isDefault());
        $this->assertTrue($template->isActive());
    }

    public function testCreateTemplateWithInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('缺少必需字段: templateName');

        $templateData = [
            'templateType' => 'safety',
        ];

        $this->service->createTemplate($templateData);
    }

    public function testCreateTemplateWithInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的模板类型');

        $templateData = [
            'templateName' => '测试模板',
            'templateType' => 'invalid_type',
        ];

        $this->service->createTemplate($templateData);
    }

    public function testUpdateTemplate(): void
    {
        $templateId = 'template123';
        $existingTemplate = new CertificateTemplate();
        $existingTemplate->setTemplateName('原始模板');
        $existingTemplate->setTemplateType('safety');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($existingTemplate);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updateData = [
            'templateName' => '更新后的模板',
            'templateType' => 'skill',
        ];

        $updatedTemplate = $this->service->updateTemplate($templateId, $updateData);

        $this->assertEquals('更新后的模板', $updatedTemplate->getTemplateName());
        $this->assertEquals('skill', $updatedTemplate->getTemplateType());
    }

    public function testUpdateNonExistentTemplate(): void
    {
        $templateId = 'nonexistent';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        $this->service->updateTemplate($templateId, []);
    }

    public function testRenderCertificate(): void
    {
        $templateId = 'template123';
        $template = new CertificateTemplate();
        $template->setTemplateName('测试模板');
        $template->setIsActive(true);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($template);

        $data = ['userName' => '张三', 'courseName' => '安全培训'];
        $result = $this->service->renderCertificate($templateId, $data);

        $this->assertIsString($result);
        $this->assertStringContainsString('测试模板', $result);
    }

    public function testRenderCertificateWithInactiveTemplate(): void
    {
        $templateId = 'template123';
        $template = new CertificateTemplate();
        $template->setTemplateName('测试模板');
        $template->setIsActive(false);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($template);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板未启用');

        $this->service->renderCertificate($templateId, []);
    }

    public function testPreviewTemplate(): void
    {
        $templateId = 'template123';
        $template = new CertificateTemplate();
        $template->setTemplateName('预览模板');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($template);

        $sampleData = ['userName' => '示例用户'];
        $result = $this->service->previewTemplate($templateId, $sampleData);

        $this->assertIsString($result);
        $this->assertStringContainsString('预览模板', $result);
    }

    public function testValidateTemplate(): void
    {
        $templateId = 'template123';
        $template = new CertificateTemplate();
        $template->setTemplateName('验证模板');
        $template->setTemplatePath('/nonexistent/path');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($template);

        $result = $this->service->validateTemplate($templateId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertFalse($result['valid']);
        $this->assertContains('模板文件不存在', $result['errors']);
    }

    public function testDuplicateTemplate(): void
    {
        $sourceTemplateId = 'source123';
        $sourceTemplate = new CertificateTemplate();
        $sourceTemplate->setTemplateName('原始模板');
        $sourceTemplate->setTemplateType('safety');
        $sourceTemplate->setTemplatePath('/templates/safety.pdf');
        $sourceTemplate->setTemplateConfig(['test' => 'config']);
        $sourceTemplate->setFieldMapping(['test' => 'mapping']);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($sourceTemplateId)
            ->willReturn($sourceTemplate);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(CertificateTemplate::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $duplicatedTemplate = $this->service->duplicateTemplate($sourceTemplateId);

        $this->assertInstanceOf(CertificateTemplate::class, $duplicatedTemplate);
        $this->assertEquals('原始模板 (副本)', $duplicatedTemplate->getTemplateName());
        $this->assertEquals('safety', $duplicatedTemplate->getTemplateType());
        $this->assertEquals('/templates/safety.pdf', $duplicatedTemplate->getTemplatePath());
        $this->assertEquals(['test' => 'config'], $duplicatedTemplate->getTemplateConfig());
        $this->assertEquals(['test' => 'mapping'], $duplicatedTemplate->getFieldMapping());
        $this->assertFalse($duplicatedTemplate->isDefault()); // 副本不能是默认模板
        $this->assertFalse($duplicatedTemplate->isActive()); // 副本默认不启用
    }

    public function testGetAvailableTemplates(): void
    {
        $templates = [
            new CertificateTemplate(),
            new CertificateTemplate(),
        ];

        $this->repository->expects($this->once())
            ->method('findActiveTemplates')
            ->willReturn($templates);

        $result = $this->service->getAvailableTemplates();

        $this->assertEquals($templates, $result);
    }

    public function testGetAvailableTemplatesByType(): void
    {
        $type = 'safety';
        $templates = [new CertificateTemplate()];

        $this->repository->expects($this->once())
            ->method('findByType')
            ->with($type)
            ->willReturn($templates);

        $result = $this->service->getAvailableTemplates($type);

        $this->assertEquals($templates, $result);
    }

    public function testGetDefaultTemplate(): void
    {
        $template = new CertificateTemplate();

        $this->repository->expects($this->once())
            ->method('findDefaultTemplate')
            ->willReturn($template);

        $result = $this->service->getDefaultTemplate();

        $this->assertEquals($template, $result);
    }

    public function testGetDefaultTemplateByType(): void
    {
        $type = 'safety';
        $template = new CertificateTemplate();

        $this->repository->expects($this->once())
            ->method('findDefaultTemplateByType')
            ->with($type)
            ->willReturn($template);

        $result = $this->service->getDefaultTemplate($type);

        $this->assertEquals($template, $result);
    }
} 