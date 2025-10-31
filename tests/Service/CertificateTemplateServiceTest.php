<?php

namespace Tourze\TrainCertBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;
use Tourze\TrainCertBundle\Service\CertificateTemplateService;

/**
 * 证书模板服务集成测试
 * 测试模板的完整生命周期：创建、更新、查询、验证、复制等
 *
 * @internal
 */
#[CoversClass(CertificateTemplateService::class)]
#[RunTestsInSeparateProcesses]
final class CertificateTemplateServiceTest extends AbstractIntegrationTestCase
{
    private CertificateTemplateService $service;

    private CertificateTemplateRepository $repository;

    protected function onSetUp(): void
    {
        $this->service = self::getService(CertificateTemplateService::class);
        $this->repository = self::getService(CertificateTemplateRepository::class);

        // 清理测试数据
        $this->clearTestData();
    }

    public function testCreateTemplate(): void
    {
        $templateData = [
            'templateName' => '安全生产证书模板',
            'templateType' => 'safety',
            'templatePath' => '/templates/safety_cert.html',
            'templateConfig' => ['width' => 800, 'height' => 600],
            'fieldMapping' => ['name' => 'userName', 'date' => 'issueDate'],
            'isDefault' => true,
            'isActive' => true,
        ];

        $template = $this->service->createTemplate($templateData);

        $this->assertNotNull($template->getId());
        $this->assertSame('安全生产证书模板', $template->getTemplateName());
        $this->assertSame('safety', $template->getTemplateType());
        $this->assertSame('/templates/safety_cert.html', $template->getTemplatePath());
        $this->assertSame(['width' => 800, 'height' => 600], $template->getTemplateConfig());
        $this->assertSame(['name' => 'userName', 'date' => 'issueDate'], $template->getFieldMapping());
        $this->assertTrue($template->isDefault());
        $this->assertTrue($template->isActive());

        // 验证数据已持久化到数据库
        self::getEntityManager()->clear();
        $persistedTemplate = $this->repository->find($template->getId());
        $this->assertNotNull($persistedTemplate);
        $this->assertSame('安全生产证书模板', $persistedTemplate->getTemplateName());
    }

    public function testCreateTemplateWithMinimalData(): void
    {
        $templateData = [
            'templateName' => '简单模板',
            'templateType' => 'skill',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ];

        $template = $this->service->createTemplate($templateData);

        $this->assertSame('简单模板', $template->getTemplateName());
        $this->assertSame('skill', $template->getTemplateType());
        $this->assertNull($template->getTemplatePath());
        $this->assertSame([], $template->getTemplateConfig());
        $this->assertSame([], $template->getFieldMapping());
        $this->assertFalse($template->isDefault());
        $this->assertTrue($template->isActive());
    }

    public function testCreateTemplateThrowsExceptionForMissingRequiredFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('缺少必需字段: templateName');

        $this->service->createTemplate(['templateType' => 'safety']);
    }

    public function testCreateTemplateThrowsExceptionForInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的模板类型');

        $templateData = [
            'templateName' => '无效类型模板',
            'templateType' => 'invalid_type',
        ];

        $this->service->createTemplate($templateData);
    }

    public function testCreateTemplateHandlesDefaultTemplateExclusivity(): void
    {
        // 创建第一个默认模板
        $firstTemplateData = [
            'templateName' => '第一个默认模板',
            'templateType' => 'safety',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => true,
            'isActive' => true,
        ];
        $firstTemplate = $this->service->createTemplate($firstTemplateData);
        $this->assertTrue($firstTemplate->isDefault());

        // 创建第二个默认模板，应该取消第一个的默认状态
        $secondTemplateData = [
            'templateName' => '第二个默认模板',
            'templateType' => 'safety',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => true,
            'isActive' => true,
        ];
        $secondTemplate = $this->service->createTemplate($secondTemplateData);

        // 重新获取第一个模板，验证默认状态已被取消
        self::getEntityManager()->refresh($firstTemplate);
        $this->assertFalse($firstTemplate->isDefault());
        $this->assertTrue($secondTemplate->isDefault());
    }

    public function testUpdateTemplate(): void
    {
        // 创建原始模板
        $originalData = [
            'templateName' => '原始模板',
            'templateType' => 'management',
            'templatePath' => '/old/path.html',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ];
        $template = $this->service->createTemplate($originalData);

        // 更新模板
        $updateData = [
            'templateName' => '更新后的模板',
            'templatePath' => '/new/path.html',
            'templateConfig' => ['updated' => true],
            'isActive' => false,
        ];

        $templateId = $template->getId();
        $this->assertNotNull($templateId, 'Template ID should not be null');
        $updatedTemplate = $this->service->updateTemplate($templateId, $updateData);

        $this->assertSame($template->getId(), $updatedTemplate->getId());
        $this->assertSame('更新后的模板', $updatedTemplate->getTemplateName());
        $this->assertSame('management', $updatedTemplate->getTemplateType()); // 未更新
        $this->assertSame('/new/path.html', $updatedTemplate->getTemplatePath());
        $this->assertSame(['updated' => true], $updatedTemplate->getTemplateConfig());
        $this->assertFalse($updatedTemplate->isActive());
    }

    public function testUpdateTemplateThrowsExceptionForNonExistentTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        $this->service->updateTemplate('non-existent-id', ['templateName' => '更新']);
    }

    public function testUpdateTemplateHandlesDefaultTemplateExclusivity(): void
    {
        // 创建两个同类型的模板
        $template1 = $this->service->createTemplate([
            'templateName' => '模板1',
            'templateType' => 'special',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => true,
            'isActive' => true,
        ]);

        $template2 = $this->service->createTemplate([
            'templateName' => '模板2',
            'templateType' => 'special',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        // 将第二个模板设为默认
        $template2Id = $template2->getId();
        $this->assertNotNull($template2Id, 'Template2 ID should not be null');
        $this->service->updateTemplate($template2Id, ['isDefault' => true]);

        // 验证第一个模板不再是默认的
        self::getEntityManager()->refresh($template1);
        self::getEntityManager()->refresh($template2);

        $this->assertFalse($template1->isDefault());
        $this->assertTrue($template2->isDefault());
    }

    public function testRenderCertificate(): void
    {
        $template = $this->service->createTemplate([
            'templateName' => '渲染模板',
            'templateType' => 'safety',
            'templatePath' => '/templates/render.html',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $data = ['userName' => '张三', 'courseName' => '安全培训'];
        $templateId = $template->getId();
        $this->assertNotNull($templateId, 'Template ID should not be null');
        $result = $this->service->renderCertificate($templateId, $data);

        // 目前是占位符实现，检查基本格式
        $this->assertStringContainsString('渲染的证书内容', $result);
        $this->assertStringContainsString('渲染模板', $result);
    }

    public function testRenderCertificateThrowsExceptionForNonExistentTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        $this->service->renderCertificate('non-existent-id', []);
    }

    public function testRenderCertificateThrowsExceptionForInactiveTemplate(): void
    {
        $template = $this->service->createTemplate([
            'templateName' => '非活跃模板',
            'templateType' => 'skill',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板未启用');

        $templateId = $template->getId();
        $this->assertNotNull($templateId, 'Template ID should not be null');
        $this->service->renderCertificate($templateId, []);
    }

    public function testPreviewTemplate(): void
    {
        $template = $this->service->createTemplate([
            'templateName' => '预览模板',
            'templateType' => 'management',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $sampleData = ['customField' => 'customValue'];
        $templateId = $template->getId();
        $this->assertNotNull($templateId, 'Template ID should not be null');
        $result = $this->service->previewTemplate($templateId, $sampleData);

        $this->assertStringContainsString('预览模板', $result);
    }

    public function testPreviewTemplateThrowsExceptionForNonExistentTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        $this->service->previewTemplate('non-existent-id', []);
    }

    public function testValidateTemplate(): void
    {
        // 创建配置完整的有效模板
        $template = $this->service->createTemplate([
            'templateName' => '有效模板',
            'templateType' => 'safety',
            'templatePath' => '/valid/path.html',
            'templateConfig' => ['valid' => true],
            'fieldMapping' => ['field1' => 'value1'],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $templateId = $template->getId();
        $this->assertNotNull($templateId, 'Template ID should not be null');
        $result = $this->service->validateTemplate($templateId);

        $this->assertFalse($result['valid']); // 文件不存在所以无效
        $errors = $result['errors'];
        $this->assertIsArray($errors);
        $this->assertContains('模板文件不存在', $errors);
        $this->assertEmpty($result['warnings']);
    }

    public function testValidateTemplateWithEmptyPath(): void
    {
        $template = $this->service->createTemplate([
            'templateName' => '无路径模板',
            'templateType' => 'skill',
            'templatePath' => '',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $templateId = $template->getId();
        $this->assertNotNull($templateId, 'Template ID should not be null');
        $result = $this->service->validateTemplate($templateId);

        $this->assertFalse($result['valid']);
        $errors = $result['errors'];
        $this->assertIsArray($errors);
        $this->assertContains('模板路径不能为空', $errors);
    }

    public function testValidateTemplateWithWarnings(): void
    {
        $template = $this->service->createTemplate([
            'templateName' => '警告模板',
            'templateType' => 'special',
            'templatePath' => '/some/path.html',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $templateId = $template->getId();
        $this->assertNotNull($templateId, 'Template ID should not be null');
        $result = $this->service->validateTemplate($templateId);

        $warnings = $result['warnings'];
        $this->assertIsArray($warnings);
        $this->assertContains('模板配置为空', $warnings);
        $this->assertContains('字段映射为空', $warnings);
    }

    public function testDuplicateTemplate(): void
    {
        $originalTemplate = $this->service->createTemplate([
            'templateName' => '原始模板',
            'templateType' => 'safety',
            'templatePath' => '/original/path.html',
            'templateConfig' => ['original' => true],
            'fieldMapping' => ['orig' => 'field'],
            'isDefault' => true,
            'isActive' => true,
        ]);

        $originalTemplateId = $originalTemplate->getId();
        $this->assertNotNull($originalTemplateId, 'Original template ID should not be null');
        $duplicatedTemplate = $this->service->duplicateTemplate($originalTemplateId);

        // 验证复制的属性
        $this->assertSame('原始模板 (副本)', $duplicatedTemplate->getTemplateName());
        $this->assertSame('safety', $duplicatedTemplate->getTemplateType());
        $this->assertSame('/original/path.html', $duplicatedTemplate->getTemplatePath());
        $this->assertSame(['original' => true], $duplicatedTemplate->getTemplateConfig());
        $this->assertSame(['orig' => 'field'], $duplicatedTemplate->getFieldMapping());

        // 验证副本的特殊属性
        $this->assertFalse($duplicatedTemplate->isDefault()); // 副本不能是默认的
        $this->assertFalse($duplicatedTemplate->isActive());  // 副本默认不启用

        // 验证是不同的实体
        $this->assertNotSame($originalTemplate->getId(), $duplicatedTemplate->getId());
    }

    public function testDuplicateTemplateThrowsExceptionForNonExistentTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('源证书模板不存在');

        $this->service->duplicateTemplate('non-existent-id');
    }

    public function testGetAvailableTemplatesAll(): void
    {
        // 创建多种模板
        $activeTemplate1 = $this->service->createTemplate([
            'templateName' => '活跃模板1',
            'templateType' => 'safety',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $activeTemplate2 = $this->service->createTemplate([
            'templateName' => '活跃模板2',
            'templateType' => 'skill',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $inactiveTemplate = $this->service->createTemplate([
            'templateName' => '非活跃模板',
            'templateType' => 'management',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => false,
        ]);

        $availableTemplates = $this->service->getAvailableTemplates();

        $this->assertCount(2, $availableTemplates);

        $templateNames = array_map(fn ($t) => $t->getTemplateName(), $availableTemplates);
        $this->assertContains('活跃模板1', $templateNames);
        $this->assertContains('活跃模板2', $templateNames);
        $this->assertNotContains('非活跃模板', $templateNames);
    }

    public function testGetAvailableTemplatesByType(): void
    {
        $this->service->createTemplate([
            'templateName' => '安全模板',
            'templateType' => 'safety',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $this->service->createTemplate([
            'templateName' => '技能模板',
            'templateType' => 'skill',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $safetyTemplates = $this->service->getAvailableTemplates('safety');
        $this->assertCount(1, $safetyTemplates);
        $this->assertSame('安全模板', $safetyTemplates[0]->getTemplateName());

        $skillTemplates = $this->service->getAvailableTemplates('skill');
        $this->assertCount(1, $skillTemplates);
        $this->assertSame('技能模板', $skillTemplates[0]->getTemplateName());

        $nonExistentTypeTemplates = $this->service->getAvailableTemplates('nonexistent');
        $this->assertEmpty($nonExistentTypeTemplates);
    }

    public function testGetDefaultTemplate(): void
    {
        // 创建默认和非默认模板
        $defaultTemplate = $this->service->createTemplate([
            'templateName' => '默认模板',
            'templateType' => 'safety',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => true,
            'isActive' => true,
        ]);

        $this->service->createTemplate([
            'templateName' => '非默认模板',
            'templateType' => 'safety',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $foundDefault = $this->service->getDefaultTemplate();
        $this->assertNotNull($foundDefault);
        $this->assertSame($defaultTemplate->getId(), $foundDefault->getId());
    }

    public function testGetDefaultTemplateByType(): void
    {
        $this->service->createTemplate([
            'templateName' => '安全默认模板',
            'templateType' => 'safety',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => true,
            'isActive' => true,
        ]);

        $this->service->createTemplate([
            'templateName' => '技能默认模板',
            'templateType' => 'skill',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => true,
            'isActive' => true,
        ]);

        $safetyDefault = $this->service->getDefaultTemplate('safety');
        $this->assertNotNull($safetyDefault);
        $this->assertSame('安全默认模板', $safetyDefault->getTemplateName());

        $skillDefault = $this->service->getDefaultTemplate('skill');
        $this->assertNotNull($skillDefault);
        $this->assertSame('技能默认模板', $skillDefault->getTemplateName());

        $nonExistentDefault = $this->service->getDefaultTemplate('nonexistent');
        $this->assertNull($nonExistentDefault);
    }

    public function testGetDefaultTemplateReturnsNullWhenNoDefault(): void
    {
        $this->service->createTemplate([
            'templateName' => '非默认模板',
            'templateType' => 'safety',
            'templateConfig' => [],
            'fieldMapping' => [],
            'isDefault' => false,
            'isActive' => true,
        ]);

        $defaultTemplate = $this->service->getDefaultTemplate();
        $this->assertNull($defaultTemplate);
    }

    /**
     * 清理数据库中的测试数据
     */
    private function clearTestData(): void
    {
        $connection = self::getEntityManager()->getConnection();
        $connection->executeStatement('DELETE FROM job_training_certificate_template');
    }
}
