<?php

namespace Tourze\TrainCertBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;

/**
 * 证书模板服务类
 * 负责证书模板的管理、渲染、验证等功能
 */
class CertificateTemplateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CertificateTemplateRepository $templateRepository
    ) {
    }

    /**
     * 创建证书模板
     * 
     * @param array $templateData 模板数据
     * @return CertificateTemplate 创建的模板
     */
    public function createTemplate(array $templateData): CertificateTemplate
    {
        $this->validateTemplateData($templateData);

        $template = new CertificateTemplate();
        $template->setTemplateName($templateData['templateName']);
        $template->setTemplateType($templateData['templateType']);
        $template->setTemplatePath($templateData['templatePath'] ?? '');
        $template->setTemplateConfig($templateData['templateConfig'] ?? []);
        $template->setFieldMapping($templateData['fieldMapping'] ?? []);
        $template->setIsDefault($templateData['isDefault'] ?? false);
        $template->setIsActive($templateData['isActive'] ?? true);

        // 如果设置为默认模板，需要取消其他同类型的默认模板
        if ($template->isDefault()) {
            $this->clearDefaultTemplate($template->getTemplateType());
        }

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $template;
    }

    /**
     * 更新证书模板
     * 
     * @param string $templateId 模板ID
     * @param array $templateData 模板数据
     * @return CertificateTemplate 更新的模板
     */
    public function updateTemplate(string $templateId, array $templateData): CertificateTemplate
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('证书模板不存在');
        }

        $this->validateTemplateData($templateData);

        if ((bool) isset($templateData['templateName'])) {
            $template->setTemplateName($templateData['templateName']);
        }
        if ((bool) isset($templateData['templateType'])) {
            $template->setTemplateType($templateData['templateType']);
        }
        if ((bool) isset($templateData['templatePath'])) {
            $template->setTemplatePath($templateData['templatePath']);
        }
        if ((bool) isset($templateData['templateConfig'])) {
            $template->setTemplateConfig($templateData['templateConfig']);
        }
        if ((bool) isset($templateData['fieldMapping'])) {
            $template->setFieldMapping($templateData['fieldMapping']);
        }
        if ((bool) isset($templateData['isDefault'])) {
            $template->setIsDefault($templateData['isDefault']);
            
            // 如果设置为默认模板，需要取消其他同类型的默认模板
            if ($template->isDefault()) {
                $this->clearDefaultTemplate($template->getTemplateType(), $templateId);
            }
        }
        if ((bool) isset($templateData['isActive'])) {
            $template->setIsActive($templateData['isActive']);
        }

        $this->entityManager->flush();

        return $template;
    }

    /**
     * 渲染证书
     * 
     * @param string $templateId 模板ID
     * @param array $data 证书数据
     * @return string 渲染结果
     */
    public function renderCertificate(string $templateId, array $data): string
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('证书模板不存在');
        }

        if (!$template->isActive()) {
            throw new \InvalidArgumentException('证书模板未启用');
        }

        // 根据模板配置渲染证书
        return $this->processTemplate($template, $data);
    }

    /**
     * 预览模板
     * 
     * @param string $templateId 模板ID
     * @param array $sampleData 示例数据
     * @return string 预览结果
     */
    public function previewTemplate(string $templateId, array $sampleData): string
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('证书模板不存在');
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
     * @return array 验证结果
     */
    public function validateTemplate(string $templateId): array
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('证书模板不存在');
        }

        $errors = [];
        $warnings = [];

        // 检查模板路径
        if (empty($template->getTemplatePath())) {
            $errors[] = '模板路径不能为空';
        } elseif (!file_exists($template->getTemplatePath())) {
            $errors[] = '模板文件不存在';
        }

        // 检查模板配置
        $config = $template->getTemplateConfig();
        if ((bool) empty($config)) {
            $warnings[] = '模板配置为空';
        }

        // 检查字段映射
        $fieldMapping = $template->getFieldMapping();
        if ((bool) empty($fieldMapping)) {
            $warnings[] = '字段映射为空';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * 复制模板
     * 
     * @param string $templateId 源模板ID
     * @return CertificateTemplate 复制的模板
     */
    public function duplicateTemplate(string $templateId): CertificateTemplate
    {
        $sourceTemplate = $this->templateRepository->find($templateId);
        if (!$sourceTemplate) {
            throw new \InvalidArgumentException('源证书模板不存在');
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
     * @return CertificateTemplate[] 模板列表
     */
    public function getAvailableTemplates(?string $type = null): array
    {
        if ((bool) $type) {
            return $this->templateRepository->findByType($type);
        }

        return $this->templateRepository->findActiveTemplates();
    }

    /**
     * 获取默认模板
     * 
     * @param string|null $type 模板类型
     * @return CertificateTemplate|null 默认模板
     */
    public function getDefaultTemplate(?string $type = null): ?CertificateTemplate
    {
        if ((bool) $type) {
            return $this->templateRepository->findDefaultTemplateByType($type);
        }

        return $this->templateRepository->findDefaultTemplate();
    }

    /**
     * 验证模板数据
     * 
     * @param array $templateData 模板数据
     * @throws \InvalidArgumentException
     */
    private function validateTemplateData(array $templateData): void
    {
        $requiredFields = ['templateName', 'templateType'];
        
        foreach ($requiredFields as $field) {
            if (!isset($templateData[$field]) || empty($templateData[$field])) {
                throw new \InvalidArgumentException("缺少必需字段: {$field}");
            }
        }

        $validTypes = ['safety', 'skill', 'management', 'special'];
        if (!in_array($templateData['templateType'], $validTypes)) {
            throw new \InvalidArgumentException('无效的模板类型');
        }
    }

    /**
     * 清除默认模板设置
     * 
     * @param string $templateType 模板类型
     * @param string|null $excludeId 排除的模板ID
     */
    private function clearDefaultTemplate(string $templateType, ?string $excludeId = null): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(CertificateTemplate::class, 't')
           ->set('t.isDefault', ':false')
           ->where('t.templateType = :type')
           ->andWhere('t.isDefault = :true')
           ->setParameter('false', false)
           ->setParameter('type', $templateType)
           ->setParameter('true', true);

        if ((bool) $excludeId) {
            $qb->andWhere('t.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        $qb->getQuery()->execute();
    }

    /**
     * 处理模板渲染
     * 
     * @param CertificateTemplate $template 模板对象
     * @param array $data 数据
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
     * @return array 示例数据
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
} 