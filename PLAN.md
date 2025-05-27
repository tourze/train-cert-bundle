# train-cert-bundle 开发计划

## 1. 功能描述

培训证书管理包，负责安全生产培训证书的全生命周期管理。包括证书模板管理、证书自动生成、证书验证查询、电子签章、证书打印、证书有效期管理等功能。支持多种证书类型，实现证书防伪技术，满足安全生产培训的证书管理要求。

## 2. 完整能力要求

### 2.1 现有能力

- ✅ 证书基本信息管理（Certificate）
- ✅ 证书与用户关联
- ✅ 证书图片存储
- ✅ 证书有效性标记
- ✅ 时间戳和用户追踪
- ✅ EasyAdmin管理界面

### 2.2 需要增强的能力

#### 2.2.1 符合规范要求的证书格式

- [ ] 标准化证书模板设计
- [ ] 证书编号规则管理
- [ ] 证书二维码生成
- [ ] 证书防伪水印
- [ ] 多语言证书支持

#### 2.2.2 证书防伪技术

- [ ] 唯一标识码
- [ ] 在线验证系统

#### 2.2.3 证书批量生成

- [ ] 批量证书生成任务
- [ ] 批量打印管理

#### 2.2.4 证书生命周期管理

- [ ] 证书申请流程
- [ ] 证书审核机制
- [ ] 证书发放管理

#### 2.2.5 证书验证和查询

- [ ] 在线证书验证
- [ ] 证书真伪查询
- [ ] 证书状态查询

## 3. 现有实体设计分析

### 3.1 现有实体

#### Certificate（证书）

- **字段**: id, title, user, imgUrl, valid
- **特性**: 支持用户关联、图片存储、有效性标记、时间戳、用户追踪
- **注释**: 基础证书信息，需要扩展更多字段

### 3.2 需要新增的实体

#### CertificateTemplate（证书模板）

```php
class CertificateTemplate
{
    private string $id;
    private string $templateName;
    private Category $category;
    private string $templateType;  // 证书类型
    private string $templatePath;  // 模板文件路径
    private array $templateConfig;  // 模板配置
    private array $fieldMapping;  // 字段映射
    private bool $isDefault;  // 是否默认模板
    private bool $isActive;  // 是否启用
    private \DateTimeInterface $createTime;
    private \DateTimeInterface $updateTime;
}
```

#### CertificateApplication（证书申请）

```php
class CertificateApplication
{
    private string $id;
    private string $userId;
    private Course $course;
    private Category $category;
    private string $applicationType;  // 申请类型
    private string $applicationStatus;  // 申请状态
    private array $applicationData;  // 申请数据
    private array $requiredDocuments;  // 必需文档
    private string $reviewComment;  // 审核意见
    private string $reviewer;  // 审核人
    private \DateTimeInterface $applicationTime;
    private \DateTimeInterface $reviewTime;
    private \DateTimeInterface $createTime;
}
```

#### CertificateRecord（证书记录）

```php
class CertificateRecord
{
    private string $id;
    private Certificate $certificate;
    private string $certificateNumber;  // 证书编号
    private string $certificateType;  // 证书类型
    private \DateTimeInterface $issueDate;  // 发证日期
    private \DateTimeInterface $expiryDate;  // 到期日期
    private string $issuingAuthority;  // 发证机构
    private string $verificationCode;  // 验证码
    private array $metadata;  // 元数据
    private \DateTimeInterface $createTime;
}
```

#### CertificateVerification（证书验证）

```php
class CertificateVerification
{
    private string $id;
    private Certificate $certificate;
    private string $verificationMethod;  // 验证方式
    private string $verifierInfo;  // 验证者信息
    private bool $verificationResult;  // 验证结果
    private array $verificationDetails;  // 验证详情
    private string $ipAddress;  // 验证IP
    private string $userAgent;  // 用户代理
    private \DateTimeInterface $verificationTime;
}
```

#### CertificateAudit（证书审核）

```php
class CertificateAudit
{
    private string $id;
    private CertificateApplication $application;
    private string $auditStatus;  // 审核状态
    private string $auditResult;  // 审核结果
    private string $auditComment;  // 审核意见
    private array $auditDetails;  // 审核详情
    private string $auditor;  // 审核人
    private \DateTimeInterface $auditTime;
    private \DateTimeInterface $createTime;
}
```

## 4. 服务设计

### 4.1 新增服务

#### CertificateService

```php
class CertificateService
{
    public function generateCertificate(string $userId, string $courseId, string $templateId): Certificate;
    public function applyCertificate(string $userId, string $courseId, array $applicationData): CertificateApplication;
    public function auditCertificate(string $applicationId, string $auditResult, string $comment): CertificateAudit;
    public function issueCertificate(string $applicationId): Certificate;
}
```

#### CertificateTemplateService

```php
class CertificateTemplateService
{
    public function createTemplate(array $templateData): CertificateTemplate;
    public function updateTemplate(string $templateId, array $templateData): CertificateTemplate;
    public function renderCertificate(string $templateId, array $data): string;
    public function previewTemplate(string $templateId, array $sampleData): string;
    public function validateTemplate(string $templateId): array;
    public function duplicateTemplate(string $templateId): CertificateTemplate;
}
```

#### CertificateGeneratorService

```php
class CertificateGeneratorService
{
    public function generateSingleCertificate(string $userId, string $templateId, array $data): Certificate;
    public function generateBatchCertificates(array $userIds, string $templateId, array $config): array;
    public function generateVerificationCode(string $certificateId): string;
}
```

#### CertificateVerificationService

```php
class CertificateVerificationService
{
    public function verifyCertificate(string $certificateNumber): array;
    public function verifyByVerificationCode(string $verificationCode): array;
    public function recordVerification(string $certificateId, array $verificationData): CertificateVerification;
}
```

#### CertificateNotificationService

```php
class CertificateNotificationService
{
    public function sendCertificateIssueNotification(string $certificateId): void;
    public function sendVerificationNotification(string $certificateId, array $verificationData): void;
}
```

## 5. Command设计

### 5.1 证书生成命令

#### CertificateGenerateCommand

```php
class CertificateGenerateCommand extends Command
{
    protected static $defaultName = 'certificate:generate';
    
    // 批量生成证书
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.2 证书管理命令

### 5.3 数据处理命令

#### CertificateStatisticsCommand

```php
class CertificateStatisticsCommand extends Command
{
    protected static $defaultName = 'certificate:statistics';
    
    // 生成证书统计报告
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.4 维护命令

#### CertificateCleanupCommand

```php
class CertificateCleanupCommand extends Command
{
    protected static $defaultName = 'certificate:cleanup';
    
    // 清理过期和无效证书
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

## 6. 依赖包

- `train-course-bundle` - 课程信息
- `doctrine-entity-checker-bundle` - 实体检查
- `doctrine-timestamp-bundle` - 时间戳管理

## 7. 测试计划

### 7.1 单元测试

- [ ] Certificate实体测试
- [ ] CertificateService测试
- [ ] CertificateGeneratorService测试
- [ ] CertificateVerificationService测试

### 7.2 集成测试

- [ ] 证书申请流程测试
- [ ] 证书生成流程测试
- [ ] 证书验证流程测试
- [ ] 批量生成测试

---

**文档版本**: v1.0
**创建日期**: 2024年12月
**负责人**: 开发团队
