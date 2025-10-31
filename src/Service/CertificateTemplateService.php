<?php

namespace Tourze\TrainCertBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Exception\InvalidArgumentException;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;

/**
 * 证书模板服务类
 * 负责证书模板的管理、渲染、验证等功能
 */
class CertificateTemplateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CertificateTemplateRepository $templateRepository,
        private readonly TemplateValidationService $validationService,
    ) {
    }

    /**
     * 创建证书模板
     *
     * @param array<string, mixed> $templateData 模板数据
     *
     * @return CertificateTemplate 创建的模板
     */
    public function createTemplate(array $templateData): CertificateTemplate
    {
        $this->validationService->validateTemplateData($templateData);
        $template = $this->buildTemplateFromData($templateData);
        $this->handleDefaultTemplateCreation($template);
        $this->persistTemplate($template);

        return $template;
    }

    /**
     * 更新证书模板
     *
     * @param string $templateId   模板ID
     * @param array<string, mixed> $templateData 模板数据
     *
     * @return CertificateTemplate 更新的模板
     */
    public function updateTemplate(string $templateId, array $templateData): CertificateTemplate
    {
        $template = $this->findTemplateOrThrow($templateId);
        $this->validationService->validateUpdateData($templateData);
        $this->applyTemplateUpdates($template, $templateData);
        $this->entityManager->flush();

        return $template;
    }

    /**
     * 查找模板或抛出异常
     */
    private function findTemplateOrThrow(string $templateId): CertificateTemplate
    {
        $template = $this->templateRepository->find($templateId);
        if (null === $template) {
            throw new InvalidArgumentException('证书模板不存在');
        }

        return $template;
    }

    /**
     * 应用模板更新
     *
     * @param CertificateTemplate     $template     模板对象
     * @param array<string, mixed>    $templateData 模板数据
     */
    private function applyTemplateUpdates(CertificateTemplate $template, array $templateData): void
    {
        $this->updateTemplateName($template, $templateData);
        $this->updateTemplateType($template, $templateData);
        $this->updateTemplatePath($template, $templateData);
        $this->updateTemplateConfig($template, $templateData);
        $this->updateFieldMapping($template, $templateData);
        $this->updateTemplateStatus($template, $templateData);
        $this->updateDefaultStatus($template, $templateData);
    }

    /**
     * 处理默认模板更新
     */
    private function handleDefaultTemplateUpdate(CertificateTemplate $template, bool $isDefault): void
    {
        $template->setIsDefault($isDefault);

        if ($isDefault) {
            $this->clearDefaultTemplate($template->getTemplateType(), $template->getId());
        }
    }

    /**
     * 渲染证书
     *
     * @param string $templateId 模板ID
     * @param array<string, mixed> $data       证书数据
     *
     * @return string 渲染结果
     */
    public function renderCertificate(string $templateId, array $data): string
    {
        $template = $this->templateRepository->find($templateId);
        if (null === $template) {
            throw new InvalidArgumentException('证书模板不存在');
        }

        if (true !== $template->isActive()) {
            throw new InvalidArgumentException('证书模板未启用');
        }

        // 根据模板配置渲染证书
        return $this->processTemplate($template, $data);
    }

    /**
     * 预览模板
     *
     * @param string $templateId 模板ID
     * @param array<string, mixed> $sampleData 示例数据
     *
     * @return string 预览结果
     */
    public function previewTemplate(string $templateId, array $sampleData): string
    {
        $template = $this->templateRepository->find($templateId);
        if (null === $template) {
            throw new InvalidArgumentException('证书模板不存在');
        }

        // 使用示例数据渲染模板
        $defaultSampleData = $this->getDefaultSampleData();
        $mergedData = array_merge($defaultSampleData, $sampleData);

        return $this->processTemplate($template, $mergedData);
    }

    /**
     * 验证模板
     *
     * @param string $templateId 模板ID
     *
     * @return array<string, mixed> 验证结果
     */
    public function validateTemplate(string $templateId): array
    {
        $template = $this->templateRepository->find($templateId);
        if (null === $template) {
            throw new InvalidArgumentException('证书模板不存在');
        }

        $errors = [];
        $warnings = [];

        // 检查模板路径
        if (null === $template->getTemplatePath() || '' === $template->getTemplatePath()) {
            $errors[] = '模板路径不能为空';
        } elseif (!file_exists($template->getTemplatePath())) {
            $errors[] = '模板文件不存在';
        }

        // 检查模板配置
        $config = $template->getTemplateConfig();
        if (null === $config || 0 === count($config)) {
            $warnings[] = '模板配置为空';
        }

        // 检查字段映射
        $fieldMapping = $template->getFieldMapping();
        if (null === $fieldMapping || 0 === count($fieldMapping)) {
            $warnings[] = '字段映射为空';
        }

        return [
            'valid' => 0 === count($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * 复制模板
     *
     * @param string $templateId 源模板ID
     *
     * @return CertificateTemplate 复制的模板
     */
    public function duplicateTemplate(string $templateId): CertificateTemplate
    {
        $sourceTemplate = $this->templateRepository->find($templateId);
        if (null === $sourceTemplate) {
            throw new InvalidArgumentException('源证书模板不存在');
        }

        $newTemplate = new CertificateTemplate();
        $newTemplate->setTemplateName($sourceTemplate->getTemplateName() . ' (副本)');
        $newTemplate->setTemplateType($sourceTemplate->getTemplateType());
        $newTemplate->setTemplatePath($sourceTemplate->getTemplatePath());
        $newTemplate->setTemplateConfig($sourceTemplate->getTemplateConfig());
        $newTemplate->setFieldMapping($sourceTemplate->getFieldMapping());
        $newTemplate->setIsDefault(false); // 副本不能是默认模板
        $newTemplate->setIsActive(false); // 副本默认不启用

        $this->entityManager->persist($newTemplate);
        $this->entityManager->flush();

        return $newTemplate;
    }

    /**
     * 获取可用的模板列表
     *
     * @param string|null $type 模板类型
     *
     * @return CertificateTemplate[] 模板列表
     */
    public function getAvailableTemplates(?string $type = null): array
    {
        if (null !== $type && '' !== $type) {
            return $this->templateRepository->findByType($type);
        }

        return $this->templateRepository->findActiveTemplates();
    }

    /**
     * 获取默认模板
     *
     * @param string|null $type 模板类型
     *
     * @return CertificateTemplate|null 默认模板
     */
    public function getDefaultTemplate(?string $type = null): ?CertificateTemplate
    {
        if (null !== $type && '' !== $type) {
            return $this->templateRepository->findDefaultTemplateByType($type);
        }

        return $this->templateRepository->findDefaultTemplate();
    }

    /**
     * 清除默认模板设置
     *
     * @param string      $templateType 模板类型
     * @param string|null $excludeId    排除的模板ID
     */
    private function clearDefaultTemplate(string $templateType, ?string $excludeId = null): void
    {
        $this->templateRepository->clearDefaultTemplate($templateType, $excludeId);
    }

    /**
     * 处理模板渲染
     *
     * @param CertificateTemplate $template 模板对象
     * @param array<string, mixed> $data     数据
     *
     * @return string 渲染结果
     */
    private function processTemplate(CertificateTemplate $template, array $data): string
    {
        // TODO: 实现实际的模板渲染逻辑
        // 这里需要根据模板类型和配置进行实际的渲染处理

        $templatePath = $template->getTemplatePath();
        $fieldMapping = $template->getFieldMapping();

        // 暂时返回一个占位符
        return "渲染的证书内容 - 模板: {$template->getTemplateName()}";
    }

    /**
     * 获取默认示例数据
     *
     * @return array<string, mixed> 示例数据
     */
    private function getDefaultSampleData(): array
    {
        return [
            'userName' => '张三',
            'idCard' => '110101199001011234',
            'courseName' => '安全生产培训',
            'issueDate' => date('Y-m-d'),
            'expiryDate' => date('Y-m-d', strtotime('+1 year')),
            'certificateNumber' => 'CERT-' . date('Ymd') . '-123456',
        ];
    }

    /**
     * 从数据构建模板对象
     * @param array<string, mixed> $templateData
     */
    private function buildTemplateFromData(array $templateData): CertificateTemplate
    {
        $template = new CertificateTemplate();
        $fields = $this->extractTemplateFields($templateData);

        $this->validationService->validateFieldTypes(
            $fields['templateName'],
            $fields['templateType'],
            $fields['templatePath'],
            $fields['templateConfig'],
            $fields['fieldMapping'],
            $fields['isDefault'],
            $fields['isActive']
        );

        $this->setTemplateBasicFields($template, $fields);
        $this->setTemplateOptionalFields($template, $fields);

        return $template;
    }

    /**
     * 设置模板基本字段
     * @param array<string, mixed> $fields
     */
    private function setTemplateBasicFields(CertificateTemplate $template, array $fields): void
    {
        $template->setTemplateName($this->validateString($fields['templateName'], 'templateName'));
        $template->setTemplateType($this->validateString($fields['templateType'], 'templateType'));
    }

    /**
     * 设置模板可选字段
     * @param array<string, mixed> $fields
     */
    private function setTemplateOptionalFields(CertificateTemplate $template, array $fields): void
    {
        $template->setTemplatePath($this->validateNullableString($fields['templatePath'], 'templatePath'));
        $template->setTemplateConfig($this->validateNullableArray($fields['templateConfig'], 'templateConfig'));
        $template->setFieldMapping($this->validateNullableArray($fields['fieldMapping'], 'fieldMapping'));
        $template->setIsDefault($this->validateBool($fields['isDefault'], 'isDefault'));
        $template->setIsActive($this->validateBool($fields['isActive'], 'isActive'));
    }

    /**
     * 验证字符串类型
     */
    private function validateString(mixed $value, string $fieldName): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(ucfirst($fieldName) . ' must be a string');
        }

        return $value;
    }

    /**
     * 验证可空字符串类型
     */
    private function validateNullableString(mixed $value, string $fieldName): ?string
    {
        if (!is_string($value) && null !== $value) {
            throw new \InvalidArgumentException(ucfirst($fieldName) . ' must be a string or null');
        }

        return $value;
    }

    /**
     * 验证可空数组类型
     * @return array<string, mixed>|null
     */
    private function validateNullableArray(mixed $value, string $fieldName): ?array
    {
        if (!is_array($value) && null !== $value) {
            throw new \InvalidArgumentException(ucfirst($fieldName) . ' must be an array or null');
        }

        /** @var array<string, mixed>|null $value */
        return $value;
    }

    /**
     * 验证布尔类型
     */
    private function validateBool(mixed $value, string $fieldName): bool
    {
        if (!is_bool($value)) {
            throw new \InvalidArgumentException(ucfirst($fieldName) . ' must be a boolean');
        }

        return $value;
    }

    /**
     * 从原始数据中提取模板字段
     * @param array<string, mixed> $templateData
     * @return array<string, mixed>
     */
    private function extractTemplateFields(array $templateData): array
    {
        return [
            'templateName' => $this->extractStringField($templateData, 'templateName', ''),
            'templateType' => $this->extractStringField($templateData, 'templateType', ''),
            'templatePath' => $this->extractNullableStringField($templateData, 'templatePath'),
            'templateConfig' => $this->extractNullableArrayField($templateData, 'templateConfig'),
            'fieldMapping' => $this->extractNullableArrayField($templateData, 'fieldMapping'),
            'isDefault' => $this->extractNullableBoolField($templateData, 'isDefault'),
            'isActive' => $this->extractNullableBoolField($templateData, 'isActive'),
        ];
    }

    /**
     * 提取字符串字段
     * @param array<string, mixed> $data
     */
    private function extractStringField(array $data, string $key, string $default): string
    {
        return is_string($data[$key] ?? null) ? $data[$key] : $default;
    }

    /**
     * 提取可空字符串字段
     * @param array<string, mixed> $data
     */
    private function extractNullableStringField(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * 提取可空数组字段
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function extractNullableArrayField(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (!is_array($value)) {
            return [];
        }

        // 确保数组键是字符串类型
        /** @var array<string, mixed> $result */
        $result = [];
        foreach ($value as $k => $v) {
            $result[(string) $k] = $v;
        }

        return $result;
    }

    /**
     * 提取可空布尔字段
     * @param array<string, mixed> $data
     */
    private function extractNullableBoolField(array $data, string $key): bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : false;
    }

    /**
     * 处理默认模板创建
     */
    private function handleDefaultTemplateCreation(CertificateTemplate $template): void
    {
        if (true === $template->isDefault()) {
            $this->clearDefaultTemplate($template->getTemplateType());
        }
    }

    /**
     * 持久化模板
     */
    private function persistTemplate(CertificateTemplate $template): void
    {
        $this->entityManager->persist($template);
        $this->entityManager->flush();
    }

    /**
     * 更新模板名称
     * @param array<string, mixed> $templateData
     */
    private function updateTemplateName(CertificateTemplate $template, array $templateData): void
    {
        if (isset($templateData['templateName']) && is_string($templateData['templateName'])) {
            $templateName = $templateData['templateName'];
            $this->validationService->validateTemplateName($templateName);
            $template->setTemplateName($templateName);
        }
    }

    /**
     * 更新模板类型
     * @param array<string, mixed> $templateData
     */
    private function updateTemplateType(CertificateTemplate $template, array $templateData): void
    {
        if (isset($templateData['templateType']) && is_string($templateData['templateType'])) {
            $templateType = $templateData['templateType'];
            $this->validationService->validateTemplateType($templateType);
            $template->setTemplateType($templateType);
        }
    }

    /**
     * 更新模板路径
     * @param array<string, mixed> $templateData
     */
    private function updateTemplatePath(CertificateTemplate $template, array $templateData): void
    {
        if (array_key_exists('templatePath', $templateData)) {
            $templatePath = $templateData['templatePath'];
            if (is_string($templatePath) || null === $templatePath) {
                $this->validationService->validateTemplatePath($templatePath);
                $template->setTemplatePath($templatePath);
            }
        }
    }

    /**
     * 更新模板配置
     * @param array<string, mixed> $templateData
     */
    private function updateTemplateConfig(CertificateTemplate $template, array $templateData): void
    {
        if (!array_key_exists('templateConfig', $templateData)) {
            return;
        }

        $templateConfig = $templateData['templateConfig'];
        if (!is_array($templateConfig) && null !== $templateConfig) {
            return;
        }

        $this->validationService->validateTemplateConfig($templateConfig);
        $template->setTemplateConfig($this->normalizeArrayKeys($templateConfig));
    }

    /**
     * 更新字段映射
     * @param array<string, mixed> $templateData
     */
    private function updateFieldMapping(CertificateTemplate $template, array $templateData): void
    {
        if (!array_key_exists('fieldMapping', $templateData)) {
            return;
        }

        $fieldMapping = $templateData['fieldMapping'];
        if (!is_array($fieldMapping) && null !== $fieldMapping) {
            return;
        }

        $this->validationService->validateFieldMapping($fieldMapping);
        $template->setFieldMapping($this->normalizeArrayKeys($fieldMapping));
    }

    /**
     * 更新模板状态
     * @param array<string, mixed> $templateData
     */
    private function updateTemplateStatus(CertificateTemplate $template, array $templateData): void
    {
        if (array_key_exists('isActive', $templateData)) {
            $isActive = $templateData['isActive'];
            if (is_bool($isActive) || null === $isActive) {
                $this->validationService->validateActiveStatus($isActive);
                $template->setIsActive($isActive);
            }
        }
    }

    /**
     * 更新默认状态
     * @param array<string, mixed> $templateData
     */
    private function updateDefaultStatus(CertificateTemplate $template, array $templateData): void
    {
        if (isset($templateData['isDefault']) && is_bool($templateData['isDefault'])) {
            $isDefault = $templateData['isDefault'];
            $this->validationService->validateDefaultStatus($isDefault);
            $this->handleDefaultTemplateUpdate($template, $isDefault);
        }
    }

    /**
     * 规范化数组键为字符串类型
     * @param array<mixed, mixed>|null $array
     * @return array<string, mixed>|null
     */
    private function normalizeArrayKeys(?array $array): ?array
    {
        if (null === $array) {
            return null;
        }

        /** @var array<string, mixed> $result */
        $result = [];
        foreach ($array as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }
}
