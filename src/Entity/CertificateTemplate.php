<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 证书模板实体
 * 用于管理不同类型的证书模板，支持自定义模板配置和字段映射
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity]
#[ORM\Table(name: 'job_training_certificate_template', options: ['comment' => '证书模板'])]
class CertificateTemplate implements ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[TrackColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否启用', 'default' => 1])]
    private ?bool $isActive = true;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: false, options: ['comment' => '模板名称'])]
    private string $templateName;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '证书类型'])]
    private string $templateType;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '模板文件路径'])]
    private ?string $templatePath = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '模板配置'])]
    private ?array $templateConfig = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '字段映射配置'])]
    private ?array $fieldMapping = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否默认模板', 'default' => 0])]
    private ?bool $isDefault = false;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'string')]
    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模板描述'])]
    private ?string $description = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'string')]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模板内容'])]
    private ?string $templateContent = null;

    public function __toString(): string
    {
        return $this->templateName ?? '';
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive();
    }

    public function setIsActive(?bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function setTemplateName(string $templateName): void
    {
        $this->templateName = $templateName;
    }

    public function getTemplateType(): string
    {
        return $this->templateType;
    }

    public function setTemplateType(string $templateType): void
    {
        $this->templateType = $templateType;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function setTemplatePath(?string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTemplateConfig(): ?array
    {
        return $this->templateConfig;
    }

    /**
     * @param array<string, mixed>|null $templateConfig
     */
    public function setTemplateConfig(?array $templateConfig): void
    {
        $this->templateConfig = $templateConfig;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFieldMapping(): ?array
    {
        return $this->fieldMapping;
    }

    /**
     * @param array<string, mixed>|null $fieldMapping
     */
    public function setFieldMapping(?array $fieldMapping): void
    {
        $this->fieldMapping = $fieldMapping;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault();
    }

    public function setIsDefault(?bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getTemplateContent(): ?string
    {
        return $this->templateContent;
    }

    public function setTemplateContent(?string $templateContent): void
    {
        $this->templateContent = $templateContent;
    }

    /**
     * @return array<string, mixed>
     */
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
            'templateContent' => $this->getTemplateContent(),
        ];
    }
}
