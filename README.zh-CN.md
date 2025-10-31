# 培训证书管理包

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/train-cert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/train-cert-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/train-cert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/train-cert-bundle)
[![代码覆盖率](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](#)
[![License](https://img.shields.io/packagist/l/tourze/train-cert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/train-cert-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/train-cert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/train-cert-bundle)

一个全面的 Symfony 培训证书管理包，提供完整的生命周期支持，
包括申请、审批、生成和验证功能。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [系统要求](#系统要求)
- [快速开始](#快速开始)
  - [生成证书](#生成证书)
  - [验证证书](#验证证书)
  - [使用控制台命令](#使用控制台命令)
- [高级用法](#高级用法)
  - [自定义证书模板](#自定义证书模板)
  - [批量证书生成](#批量证书生成)
  - [证书申请工作流](#证书申请工作流)
- [核心组件](#核心组件)
  - [实体](#实体)
  - [服务](#服务)
  - [控制台命令](#控制台命令)
  - [管理控制器](#管理控制器)
- [配置](#配置)
  - [环境变量](#环境变量)
  - [服务配置](#服务配置)
- [安全](#安全)
  - [防伪功能](#防伪功能)
  - [访问控制](#访问控制)
  - [最佳实践](#最佳实践)
  - [安全配置](#安全配置)
- [测试](#测试)
- [依赖包](#依赖包)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- **证书模板管理** - 支持多种证书类型和自定义模板配置
- **申请工作流** - 完整的申请→审批→发放工作流程
- **证书生成** - 单个和批量证书生成功能
- **验证系统** - 多种验证方式（证书编号、验证码）
- **防伪技术** - 唯一标识符和验证码生成
- **生命周期管理** - 证书有效期管理和过期提醒
- **统计报告** - 证书发放、验证和过期统计
- **管理界面** - 基于 EasyAdmin 的管理后台

## 安装

```bash
composer require tourze/train-cert-bundle
```

## 系统要求

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- EasyAdmin Bundle 4+

## 快速开始

### 生成证书

```php
<?php

use Tourze\TrainCertBundle\Service\CertificateService;

// 为用户生成证书
$certificateService = $container->get(CertificateService::class);
$certificate = $certificateService->generateCertificate(
    'user123',
    'course456',
    'template789'
);
```

### 验证证书

```php
<?php

use Tourze\TrainCertBundle\Service\CertificateVerificationService;

// 通过证书编号验证证书
$verificationService = $container->get(CertificateVerificationService::class);
$result = $verificationService->verifyCertificate('CERT-20241201-123456');

if ($result->isValid()) {
    echo "证书有效";
}
```

### 使用控制台命令

```bash
# 为多个用户生成证书
php bin/console certificate:generate template123 --user-ids=user1,user2,user3

# 生成统计报告
php bin/console certificate:statistics --format=json

# 清理过期证书
php bin/console certificate:cleanup --expired-days=365
```

## 高级用法

### 自定义证书模板

创建具有特定配置的自定义证书模板：

```php
<?php

use Tourze\TrainCertBundle\Service\CertificateTemplateService;

$templateService = $container->get(CertificateTemplateService::class);
$template = $templateService->createTemplate([
    'templateName' => '安全生产培训证书',
    'templateType' => 'safety',
    'templatePath' => '/templates/safety.pdf',
    'templateConfig' => ['validityPeriod' => 365],
    'fieldMapping' => ['userName' => 'holder_name'],
    'isDefault' => true,
    'isActive' => true,
]);
```

### 批量证书生成

一次为多个用户生成证书：

```php
<?php

$userIds = ['user1', 'user2', 'user3'];
$templateId = 'template789';
$config = ['issuingAuthority' => '培训机构'];

$certificates = $certificateService->generateBatchCertificates(
    $userIds, 
    $templateId, 
    $config
);
```

### 证书申请工作流

实现完整的申请工作流：

```php
<?php

// 1. 申请证书
$application = $certificateService->applyCertificate(
    'user123',
    'course456',
    ['templateId' => 'template789', 'type' => 'standard']
);

// 2. 审核申请
$audit = $certificateService->auditCertificate(
    $application->getId(),
    'approved',
    '申请符合所有要求'
);

// 3. 发放证书
$certificate = $certificateService->issueCertificate($application->getId());
```

## 核心组件

### 实体

1. **Certificate** - 基础证书信息
2. **CertificateTemplate** - 模板管理
3. **CertificateApplication** - 申请记录
4. **CertificateRecord** - 详细证书记录
5. **CertificateVerification** - 验证历史
6. **CertificateAudit** - 审核记录

### 服务

1. **CertificateService** - 核心业务逻辑
2. **CertificateTemplateService** - 模板管理
3. **CertificateGeneratorService** - 证书生成
4. **CertificateVerificationService** - 证书验证
5. **CertificateNotificationService** - 通知服务

### 控制台命令

- `certificate:generate` - 生成证书
- `certificate:statistics` - 生成统计报告
- `certificate:cleanup` - 清理过期数据
- `job-training:generate-certificate-image` - 生成证书图片

### 管理控制器

- `CertificateTemplateCrudController` - 模板管理
- `CertificateApplicationCrudController` - 申请管理
- `CertificateVerificationCrudController` - 验证管理

## 配置

### 环境变量

```env
# 证书文件存储路径
CERTIFICATE_STORAGE_PATH=/var/certificates

# 邮件通知配置
MAILER_DSN=smtp://localhost:587

# 证书验证码盐值
CERTIFICATE_VERIFY_SALT=your_secret_salt
```

### 服务配置

```yaml
# config/services.yaml
services:
    Tourze\TrainCertBundle\Service\:
        resource: '../packages/train-cert-bundle/src/Service/'
        autowire: true
        autoconfigure: true
```

## 安全

本包实现了多项安全措施来确保证书完整性：

### 防伪功能

- **唯一证书编号** - 每个证书都有唯一标识符（格式：CERT-YYYYMMDD-XXXXXX）
- **验证码** - 基于 SHA-256 的验证码提供额外安全保障
- **模板验证** - 只有激活和验证的模板才能使用
- **数字签名** - 支持数字签名集成

### 访问控制

- **基于角色的权限** - 证书管理需要适当的角色权限
- **审计追踪** - 所有证书操作的完整审计日志
- **安全存储** - 证书文件采用适当的访问控制存储

### 最佳实践

- 在执行证书操作前始终验证用户权限
- 为证书生成端点实施速率限制
- 所有证书相关的 API 通信使用 HTTPS
- 定期审计证书发放和验证日志

### 安全配置

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/admin/certificate, roles: ROLE_CERTIFICATE_ADMIN }
        - { path: ^/api/certificate, roles: ROLE_API_USER }
```

## 测试

```bash
# 运行所有测试
./vendor/bin/phpunit packages/train-cert-bundle/tests/
```

## 依赖包

- `tourze/doctrine-timestamp-bundle` - 时间戳管理
- `tourze/doctrine-snowflake-bundle` - 雪花 ID 生成
- `tourze/doctrine-user-bundle` - 用户管理
- `tourze/doctrine-indexed-bundle` - 数据库索引

## 贡献

欢迎提交 Issue 和 Pull Request 来改进这个包。

## 许可证

MIT 许可证。更多信息请查看 [License File](LICENSE)。