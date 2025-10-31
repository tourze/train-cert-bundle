<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;
use Tourze\TrainCertBundle\Service\TemplateValidationService;

/**
 * @internal
 */
#[CoversClass(TemplateValidationService::class)]
final class TemplateValidationServiceTest extends TestCase
{
    private TemplateValidationService $validationService;

    protected function setUp(): void
    {
        $this->validationService = new TemplateValidationService();
    }

    public function testValidateTemplateDataSuccess(): void
    {
        $templateData = [
            'templateName' => 'Test Template',
            'templateType' => 'safety',
        ];

        $this->expectNotToPerformAssertions();
        $this->validationService->validateTemplateData($templateData);
    }

    public function testValidateTemplateDataMissingRequiredField(): void
    {
        $templateData = [
            'templateName' => 'Test Template',
            // missing templateType
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('缺少必需字段: templateType');
        $this->validationService->validateTemplateData($templateData);
    }

    public function testValidateTemplateDataInvalidType(): void
    {
        $templateData = [
            'templateName' => 'Test Template',
            'templateType' => 'invalid_type',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的模板类型');
        $this->validationService->validateTemplateData($templateData);
    }

    public function testValidateUpdateDataSuccess(): void
    {
        $templateData = [
            'templateName' => 'Updated Template',
            'templateType' => 'skill',
        ];

        $this->expectNotToPerformAssertions();
        $this->validationService->validateUpdateData($templateData);
    }

    public function testValidateUpdateDataEmptyName(): void
    {
        $templateData = [
            'templateName' => '',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('模板名称不能为空');
        $this->validationService->validateUpdateData($templateData);
    }

    public function testValidateFieldTypesSuccess(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validationService->validateFieldTypes(
            'Template Name',
            'safety',
            '/path/to/template',
            [],
            [],
            false,
            true
        );
    }

    public function testValidateTemplateNameInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('模板名称必须是字符串');
        $this->validationService->validateTemplateName(123);
    }

    public function testValidateTemplateTypeInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('模板类型必须是字符串');
        $this->validationService->validateTemplateType(null);
    }

    public function testValidateTemplatePathValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validationService->validateTemplatePath('/valid/path');
        $this->validationService->validateTemplatePath(null);
    }

    public function testValidateTemplatePathInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('模板路径必须是字符串或null');
        $this->validationService->validateTemplatePath(123);
    }

    public function testValidateTemplateConfigValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validationService->validateTemplateConfig([]);
        $this->validationService->validateTemplateConfig(null);
    }

    public function testValidateTemplateConfigInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('模板配置必须是数组或null');
        $this->validationService->validateTemplateConfig('invalid');
    }

    public function testValidateFieldMappingValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validationService->validateFieldMapping([]);
        $this->validationService->validateFieldMapping(null);
    }

    public function testValidateFieldMappingInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('字段映射必须是数组或null');
        $this->validationService->validateFieldMapping('invalid');
    }

    public function testValidateActiveStatusValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validationService->validateActiveStatus(true);
        $this->validationService->validateActiveStatus(false);
        $this->validationService->validateActiveStatus(null);
    }

    public function testValidateActiveStatusInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('是否激活必须是布尔值或null');
        $this->validationService->validateActiveStatus('invalid');
    }

    public function testValidateDefaultStatusValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validationService->validateDefaultStatus(true);
        $this->validationService->validateDefaultStatus(false);
    }

    public function testValidateDefaultStatusInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('是否默认必须是布尔值');
        $this->validationService->validateDefaultStatus('invalid');
    }
}
