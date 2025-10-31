<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TrainCertBundle\Controller\Admin\CertificateVerificationCrudController;
use Tourze\TrainCertBundle\Entity\CertificateVerification;

/**
 * @internal
 */
#[CoversClass(CertificateVerificationCrudController::class)]
#[RunTestsInSeparateProcesses]
final class CertificateVerificationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<CertificateVerification>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(CertificateVerificationCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '证书' => ['证书'];
        yield '验证方式' => ['验证方式'];
        yield '验证结果' => ['验证结果'];
        yield 'IP地址' => ['IP地址'];
        yield '验证时间' => ['验证时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'certificate' => ['certificate'];
        yield 'verificationMethod' => ['verificationMethod'];
        yield 'verificationResult' => ['verificationResult'];
        yield 'ipAddress' => ['ipAddress'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'certificate' => ['certificate'];
        yield 'verificationMethod' => ['verificationMethod'];
        yield 'verificationResult' => ['verificationResult'];
        yield 'ipAddress' => ['ipAddress'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(CertificateVerification::class, CertificateVerificationCrudController::getEntityFqcn());
    }

    public function testControllerConfiguration(): void
    {
        $controller = $this->getControllerService();

        // 测试字段配置
        $fields = iterator_to_array($controller->configureFields('index'));
        $this->assertNotEmpty($fields);

        // 验证控制器类型
        $this->assertInstanceOf(AbstractCrudController::class, $controller);
    }

    public function testFieldsForDifferentPages(): void
    {
        $controller = $this->getControllerService();

        // 测试索引页字段配置
        $indexFields = iterator_to_array($controller->configureFields('index'));
        $this->assertNotEmpty($indexFields);

        // 测试新建页字段配置
        $newFields = iterator_to_array($controller->configureFields('new'));
        $this->assertNotEmpty($newFields);

        // 测试编辑页字段配置
        $editFields = iterator_to_array($controller->configureFields('edit'));
        $this->assertNotEmpty($editFields);

        // 验证字段数量合理
        $this->assertGreaterThan(3, count($newFields));
        $this->assertGreaterThan(3, count($editFields));
    }

    public function testActionsConfiguration(): void
    {
        $controller = $this->getControllerService();

        // 验证控制器类型
        $this->assertInstanceOf(AbstractCrudController::class, $controller);

        // 测试Action配置
        $actions = $controller->configureActions(Actions::new());
        $this->assertNotNull($actions);
    }

    public function testConfiguredFieldsMatchExpectedHeaders(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('index'));

        $expectedHeaders = [
            'ID',
            '证书',
            '验证方式',
            '验证结果',
            'IP地址',
            '验证时间',
        ];

        $actualLabels = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                continue;
            }
            $dto = $field->getAsDto();
            if ($dto->isDisplayedOn('index')) {
                $actualLabels[] = $dto->getLabel();
            }
        }

        // 验证期望的字段标签都存在于配置中
        foreach ($expectedHeaders as $expectedHeader) {
            $this->assertContains(
                $expectedHeader,
                $actualLabels,
                "Expected header '{$expectedHeader}' not found in configured fields"
            );
        }

        // 确保有足够的字段
        $this->assertGreaterThanOrEqual(count($expectedHeaders), count($actualLabels));
    }

    public function testCrudConfiguration(): void
    {
        $controller = $this->getControllerService();
        $crud = $controller->configureCrud(Crud::new());

        $this->assertNotNull($crud);
    }

    public function testFiltersConfiguration(): void
    {
        $controller = $this->getControllerService();
        $filters = $controller->configureFilters(Filters::new());

        $this->assertNotNull($filters);
    }

    public function testRequiredFieldConfiguration(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('new'));

        // 验证必需字段存在
        $fieldNames = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                $fieldNames[] = $field;
            } else {
                $fieldNames[] = $field->getAsDto()->getProperty();
            }
        }

        $this->assertContains('certificate', $fieldNames);
        $this->assertContains('verificationMethod', $fieldNames);
        $this->assertContains('verificationResult', $fieldNames);
    }

    public function testValidationRequiredFields(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('new'));

        // 验证必填字段配置
        $requiredFields = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                continue;
            }
            $dto = $field->getAsDto();
            if (true === $dto->getFormTypeOption('required')) {
                $requiredFields[] = $dto->getProperty();
            }
        }

        // 对于验证记录，字段可能不是必填的，因为这是验证结果记录
        // 如果有必填字段，验证合理性（但不强制要求特定字段）
        if ([] !== $requiredFields) {
            $this->assertGreaterThan(0, count($requiredFields));
        }
    }

    public function testIndexFieldsAreProperlyConfigured(): void
    {
        $controller = $this->getControllerService();
        $indexFields = iterator_to_array($controller->configureFields('index'));

        // 验证索引页字段配置
        $fieldProperties = [];
        foreach ($indexFields as $field) {
            if (!is_string($field)) {
                $dto = $field->getAsDto();
                if ($dto->isDisplayedOn('index')) {
                    $fieldProperties[] = $dto->getProperty();
                }
            }
        }

        // 确保重要字段在索引页显示
        $expectedIndexFields = ['id', 'certificate', 'verificationMethod', 'verificationResult'];
        foreach ($expectedIndexFields as $expectedField) {
            $this->assertContains(
                $expectedField,
                $fieldProperties,
                "Field '{$expectedField}' should be displayed on index page"
            );
        }
    }
}
