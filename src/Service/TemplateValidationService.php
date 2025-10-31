<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Service;

use Tourze\TrainCertBundle\Exception\InvalidArgumentException;

/**
 * 模板验证服务类
 * 负责模板数据的验证逻辑
 */
class TemplateValidationService
{
    /**
     * 验证模板数据
     *
     * @param array<string, mixed> $templateData 模板数据
     *
     * @throws InvalidArgumentException
     */
    public function validateTemplateData(array $templateData): void
    {
        $requiredFields = ['templateName', 'templateType'];

        foreach ($requiredFields as $field) {
            if (!isset($templateData[$field]) || '' === $templateData[$field]) {
                throw new InvalidArgumentException("缺少必需字段: {$field}");
            }
        }

        $validTypes = ['safety', 'skill', 'management', 'special'];
        if (!in_array($templateData['templateType'], $validTypes, true)) {
            throw new InvalidArgumentException('无效的模板类型');
        }
    }

    /**
     * 验证更新数据
     *
     * @param array<string, mixed> $templateData 模板数据
     *
     * @throws InvalidArgumentException
     */
    public function validateUpdateData(array $templateData): void
    {
        // 更新时只验证存在的字段，不要求所有必需字段
        if (isset($templateData['templateName']) && '' === $templateData['templateName']) {
            throw new InvalidArgumentException('模板名称不能为空');
        }

        if (isset($templateData['templateType'])) {
            $validTypes = ['safety', 'skill', 'management', 'special'];
            if (!in_array($templateData['templateType'], $validTypes, true)) {
                throw new InvalidArgumentException('无效的模板类型');
            }
        }
    }

    /**
     * 验证模板字段类型
     *
     * @param mixed $templateName
     * @param mixed $templateType
     * @param mixed $templatePath
     * @param mixed $templateConfig
     * @param mixed $fieldMapping
     * @param mixed $isDefault
     * @param mixed $isActive
     */
    public function validateFieldTypes(
        mixed $templateName,
        mixed $templateType,
        mixed $templatePath,
        mixed $templateConfig,
        mixed $fieldMapping,
        mixed $isDefault,
        mixed $isActive,
    ): void {
        if (!is_string($templateName)) {
            throw new InvalidArgumentException('模板名称必须是字符串');
        }
        if (!is_string($templateType)) {
            throw new InvalidArgumentException('模板类型必须是字符串');
        }
        if (null !== $templatePath && !is_string($templatePath)) {
            throw new InvalidArgumentException('模板路径必须是字符串或null');
        }
        if (!is_array($templateConfig)) {
            throw new InvalidArgumentException('模板配置必须是数组');
        }
        if (!is_array($fieldMapping)) {
            throw new InvalidArgumentException('字段映射必须是数组');
        }
        if (!is_bool($isDefault)) {
            throw new InvalidArgumentException('是否默认必须是布尔值');
        }
        if (!is_bool($isActive)) {
            throw new InvalidArgumentException('是否激活必须是布尔值');
        }
    }

    /**
     * 验证单个字段类型
     */
    public function validateTemplateName(mixed $templateName): void
    {
        if (!is_string($templateName)) {
            throw new InvalidArgumentException('模板名称必须是字符串');
        }
    }

    /**
     * 验证模板类型
     */
    public function validateTemplateType(mixed $templateType): void
    {
        if (!is_string($templateType)) {
            throw new InvalidArgumentException('模板类型必须是字符串');
        }
    }

    /**
     * 验证模板路径
     */
    public function validateTemplatePath(mixed $templatePath): void
    {
        if (!is_string($templatePath) && null !== $templatePath) {
            throw new InvalidArgumentException('模板路径必须是字符串或null');
        }
    }

    /**
     * 验证模板配置
     */
    public function validateTemplateConfig(mixed $templateConfig): void
    {
        if (!is_array($templateConfig) && null !== $templateConfig) {
            throw new InvalidArgumentException('模板配置必须是数组或null');
        }
    }

    /**
     * 验证字段映射
     */
    public function validateFieldMapping(mixed $fieldMapping): void
    {
        if (!is_array($fieldMapping) && null !== $fieldMapping) {
            throw new InvalidArgumentException('字段映射必须是数组或null');
        }
    }

    /**
     * 验证激活状态
     */
    public function validateActiveStatus(mixed $isActive): void
    {
        if (!is_bool($isActive) && null !== $isActive) {
            throw new InvalidArgumentException('是否激活必须是布尔值或null');
        }
    }

    /**
     * 验证默认状态
     */
    public function validateDefaultStatus(mixed $isDefault): void
    {
        if (!is_bool($isDefault)) {
            throw new InvalidArgumentException('是否默认必须是布尔值');
        }
    }
}
