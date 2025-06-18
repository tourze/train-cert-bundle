<?php

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Filter\Keyword;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

/**
 * 证书模板实体
 * 用于管理不同类型的证书模板，支持自定义模板配置和字段映射
 */
#[AsPermission(title: '证书模板')]
#[Deletable]
#[ORM\Entity]
#[ORM\Table(name: 'job_training_certificate_template', options: ['comment' => '证书模板'])]
class CertificateTemplate implements ApiArrayInterface
{
    use TimestampableAware;
    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[Groups(['restful_read', 'admin_curd'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]#[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Groups(['restful_read', 'admin_curd'])]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]#[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[CreatedByColumn]
    #[Groups(['restful_read'])]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[Groups(['restful_read'])]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[BoolColumn]
    #[IndexColumn]
    #[TrackColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否启用', 'default' => 1])]
    #[ListColumn(order: 97)]
    #[FormField(order: 97)]
    private ?bool $isActive = true;

    #[Keyword(inputWidth: 60, label: '模板名称')]
    #[ListColumn(order: 1)]
    #[FormField(order: 1)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 100, nullable: false, options: ['comment' => '模板名称'])]
    private string $templateName;

    #[ListColumn(order: 2)]
    #[FormField(order: 2)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '证书类型'])]
    private string $templateType;

    #[ListColumn(order: 3)]
    #[FormField(order: 3)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '模板文件路径'])]
    private ?string $templatePath = null;

    #[FormField(order: 4)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '模板配置'])]
    private ?array $templateConfig = null;

    #[FormField(order: 5)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '字段映射配置'])]
    private ?array $fieldMapping = null;

    #[BoolColumn]
    #[IndexColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否默认模板', 'default' => 0])]
    #[ListColumn(order: 6)]
    #[FormField(order: 6)]
    private ?bool $isDefault = false;

    #[FormField(order: 7)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模板描述'])]
    private ?string $description = null;

    public function __construct()
    {
        $this->createTime = new \DateTime();
        $this->updateTime = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->templateName ?? '';
    }public function getId(): ?string
    {
        return $this->id;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
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