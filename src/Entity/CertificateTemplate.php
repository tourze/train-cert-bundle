<?php

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 证书模板实体
 * 用于管理不同类型的证书模板，支持自定义模板配置和字段映射
 */
#[ORM\Entity]
#[ORM\Table(name: 'job_training_certificate_template', options: ['comment' => '证书模板'])]
class CertificateTemplate implements ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;

    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createTime = $now;
        $this->updateTime = $now;
    }

    #[TrackColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否启用', 'default' => 1])]
    private ?bool $isActive = true;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 100, nullable: false, options: ['comment' => '模板名称'])]
    private string $templateName;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '证书类型'])]
    private string $templateType;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '模板文件路径'])]
    private ?string $templatePath = null;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '模板配置'])]
    private ?array $templateConfig = null;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '字段映射配置'])]
    private ?array $fieldMapping = null;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否默认模板', 'default' => 0])]
    private ?bool $isDefault = false;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模板描述'])]
    private ?string $description = null;

    public function __toString(): string
    {
        return $this->templateName ?? '';
    }public function getId(): ?string
    {
        return $this->id;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function setTemplateName(string $templateName): self
    {
        $this->templateName = $templateName;
        return $this;
    }

    public function getTemplateType(): string
    {
        return $this->templateType;
    }

    public function setTemplateType(string $templateType): self
    {
        $this->templateType = $templateType;
        return $this;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function setTemplatePath(?string $templatePath): self
    {
        $this->templatePath = $templatePath;
        return $this;
    }

    public function getTemplateConfig(): ?array
    {
        return $this->templateConfig;
    }

    public function setTemplateConfig(?array $templateConfig): self
    {
        $this->templateConfig = $templateConfig;
        return $this;
    }

    public function getFieldMapping(): ?array
    {
        return $this->fieldMapping;
    }

    public function setFieldMapping(?array $fieldMapping): self
    {
        $this->fieldMapping = $fieldMapping;
        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(?bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'templateName' => $this->getTemplateName(),
            'templateType' => $this->getTemplateType(),
            'templatePath' => $this->getTemplatePath(),
            'templateConfig' => $this->getTemplateConfig(),
            'fieldMapping' => $this->getFieldMapping(),
            'isDefault' => $this->isDefault(),
            'isActive' => $this->isActive(),
            'description' => $this->getDescription(),
        ];
    }
} 