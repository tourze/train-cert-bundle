<?php

namespace Tourze\TrainCertBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;

/**
 * 证书模板实体测试
 */
class CertificateTemplateTest extends TestCase
{
    private CertificateTemplate $template;

    protected function setUp(): void
    {
        $this->template = new CertificateTemplate();
    }

    public function testSetAndGetTemplateName(): void
    {
        $templateName = '安全生产培训证书模板';
        $this->template->setTemplateName($templateName);
        
        $this->assertEquals($templateName, $this->template->getTemplateName());
    }

    public function testSetAndGetTemplateType(): void
    {
        $templateType = 'safety';
        $this->template->setTemplateType($templateType);
        
        $this->assertEquals($templateType, $this->template->getTemplateType());
    }

    public function testSetAndGetTemplatePath(): void
    {
        $templatePath = '/templates/safety_certificate.pdf';
        $this->template->setTemplatePath($templatePath);
        
        $this->assertEquals($templatePath, $this->template->getTemplatePath());
    }

    public function testSetAndGetTemplateConfig(): void
    {
        $config = [
            'validityPeriod' => 365,
            'backgroundColor' => '#ffffff',
            'fontSize' => 12,
        ];
        $this->template->setTemplateConfig($config);
        
        $this->assertEquals($config, $this->template->getTemplateConfig());
    }

    public function testSetAndGetFieldMapping(): void
    {
        $fieldMapping = [
            'userName' => 'holder_name',
            'courseName' => 'course_title',
            'issueDate' => 'issue_date',
        ];
        $this->template->setFieldMapping($fieldMapping);
        
        $this->assertEquals($fieldMapping, $this->template->getFieldMapping());
    }

    public function testIsDefaultAndSetIsDefault(): void
    {
        $this->assertFalse($this->template->isDefault());
        
        $this->template->setIsDefault(true);
        $this->assertTrue($this->template->isDefault());
        
        $this->template->setIsDefault(false);
        $this->assertFalse($this->template->isDefault());
    }

    public function testIsActiveAndSetIsActive(): void
    {
        $this->assertTrue($this->template->isActive()); // 默认为true
        
        $this->template->setIsActive(false);
        $this->assertFalse($this->template->isActive());
        
        $this->template->setIsActive(true);
        $this->assertTrue($this->template->isActive());
    }

    public function testToString(): void
    {
        $templateName = '安全生产培训证书模板';
        $this->template->setTemplateName($templateName);
        
        $this->assertEquals($templateName, (string) $this->template);
    }

    public function testRetrieveApiArray(): void
    {
        $this->template->setTemplateName('测试模板');
        $this->template->setTemplateType('safety');
        $this->template->setTemplatePath('/test/path');
        $this->template->setTemplateConfig(['test' => 'config']);
        $this->template->setFieldMapping(['test' => 'mapping']);
        $this->template->setIsDefault(true);
        $this->template->setIsActive(true);

        $apiArray = $this->template->retrieveApiArray();
        $this->assertEquals('测试模板', $apiArray['templateName']);
        $this->assertEquals('safety', $apiArray['templateType']);
        $this->assertEquals('/test/path', $apiArray['templatePath']);
        $this->assertEquals(['test' => 'config'], $apiArray['templateConfig']);
        $this->assertEquals(['test' => 'mapping'], $apiArray['fieldMapping']);
        $this->assertTrue($apiArray['isDefault']);
        $this->assertTrue($apiArray['isActive']);
    }

    public function testCreateTimeIsSetOnConstruction(): void
    {
        $template = new CertificateTemplate();
        
        $this->assertInstanceOf(\DateTimeInterface::class, $template->getCreateTime());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $template->getCreateTime());
    }

    public function testUpdateTimeIsSetOnConstruction(): void
    {
        $template = new CertificateTemplate();
        
        $this->assertInstanceOf(\DateTimeInterface::class, $template->getUpdateTime());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $template->getUpdateTime());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->template->getId());
    }

    public function testValidTemplateTypes(): void
    {
        $validTypes = ['safety', 'skill', 'management', 'special'];
        
        foreach ($validTypes as $type) {
            $this->template->setTemplateType($type);
            $this->assertEquals($type, $this->template->getTemplateType());
        }
    }

    public function testEmptyConfigAndMapping(): void
    {
        $this->template->setTemplateConfig([]);
        $this->template->setFieldMapping([]);
        
        $this->assertEquals([], $this->template->getTemplateConfig());
        $this->assertEquals([], $this->template->getFieldMapping());
    }
} 