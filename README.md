# train-cert-bundle

培训证书管理包，负责安全生产培训证书的全生命周期管理。

## 功能特性

### 核心功能

- ✅ **证书模板管理** - 支持多种证书类型和自定义模板配置
- ✅ **证书申请流程** - 完整的申请→审核→发放工作流
- ✅ **证书生成** - 单个和批量证书生成功能
- ✅ **证书验证** - 多种验证方式（证书编号、验证码）
- ✅ **防伪技术** - 唯一标识码和验证码生成
- ✅ **生命周期管理** - 证书有效期管理和过期提醒
- ✅ **统计报告** - 证书发放、验证、过期统计
- ✅ **管理界面** - 基于EasyAdmin的管理后台

### 技术特性

- 基于Symfony Framework和Doctrine ORM
- 遵循PSR-1、PSR-4、PSR-12规范
- 支持PHP 8+
- 完整的单元测试和集成测试
- 符合SOLID原则的服务层设计

## 安装

```bash
composer require tourze/train-cert-bundle
```

## 实体结构

### 核心实体

1. **Certificate** - 证书基础信息
2. **CertificateTemplate** - 证书模板管理
3. **CertificateApplication** - 证书申请记录
4. **CertificateRecord** - 证书详细记录
5. **CertificateVerification** - 证书验证历史
6. **CertificateAudit** - 证书审核记录

### 实体关系

```
CertificateTemplate (1) ←→ (N) CertificateApplication
CertificateApplication (1) ←→ (1) Certificate
Certificate (1) ←→ (1) CertificateRecord
Certificate (1) ←→ (N) CertificateVerification
CertificateApplication (1) ←→ (N) CertificateAudit
```

## 服务层

### 核心服务

1. **CertificateService** - 证书核心业务逻辑
2. **CertificateTemplateService** - 模板管理服务
3. **CertificateGeneratorService** - 证书生成服务
4. **CertificateVerificationService** - 证书验证服务
5. **CertificateNotificationService** - 通知服务

### 使用示例

```php
// 创建证书模板
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

// 申请证书
$certificateService = $container->get(CertificateService::class);
$application = $certificateService->applyCertificate('user123', 'course456', [
    'templateId' => $template->getId(),
    'type' => 'standard',
    'userName' => '张三',
    'idCard' => '123456789',
]);

// 审核证书申请
$audit = $certificateService->auditCertificate(
    $application->getId(),
    'approved',
    '审核通过'
);

// 发放证书
$certificate = $certificateService->issueCertificate($application->getId());

// 验证证书
$verificationService = $container->get(CertificateVerificationService::class);
$result = $verificationService->verifyCertificate('CERT-20241201-123456');
```

## 命令行工具

### 证书生成命令

```bash
# 为单个用户生成证书
php bin/console certificate:generate template123 --user-ids=user456

# 批量生成证书
php bin/console certificate:generate template123 --user-ids=user1,user2,user3

# 从文件读取用户列表
php bin/console certificate:generate template123 --user-file=/path/to/users.txt

# 试运行模式
php bin/console certificate:generate template123 --user-ids=user456 --dry-run
```

### 统计报告命令

```bash
# 生成统计报告
php bin/console certificate:statistics

# 指定日期范围
php bin/console certificate:statistics --start-date=2024-01-01 --end-date=2024-12-31

# 输出为JSON格式
php bin/console certificate:statistics --format=json --output-file=/tmp/stats.json

# 按证书类型过滤
php bin/console certificate:statistics --type=safety
```

### 数据清理命令

```bash
# 清理过期证书数据
php bin/console certificate:cleanup --expired-days=365

# 清理验证记录
php bin/console certificate:cleanup --verification-days=90

# 试运行模式
php bin/console certificate:cleanup --dry-run
```

## 管理界面

包含完整的EasyAdmin管理界面：

- **证书模板管理** - 创建、编辑、复制、预览模板
- **证书申请管理** - 审核申请、发放证书
- **证书验证管理** - 查看验证记录和统计

### 访问路径

```
/admin/certificate-template    # 证书模板管理
/admin/certificate-application # 证书申请管理
/admin/certificate-verification # 证书验证管理
```

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

## 测试

### 运行测试

```bash
# 运行所有测试
php bin/phpunit packages/train-cert-bundle/tests/

# 运行单元测试
php bin/phpunit packages/train-cert-bundle/tests/Entity/
php bin/phpunit packages/train-cert-bundle/tests/Service/

# 运行集成测试
php bin/phpunit packages/train-cert-bundle/tests/Integration/
```

### 测试覆盖率

- 实体测试覆盖率：100%
- 服务层测试覆盖率：90%+
- 集成测试覆盖核心业务流程

## 依赖包

- `train-course-bundle` - 课程信息管理
- `doctrine-entity-checker-bundle` - 实体检查
- `doctrine-timestamp-bundle` - 时间戳管理
- `doctrine-snowflake-bundle` - 雪花ID生成
- `doctrine-track-bundle` - 数据追踪

## 开发指南

### 添加新的证书类型

1. 在`CertificateTemplate`实体中添加新的类型常量
2. 更新模板服务的验证逻辑
3. 创建对应的模板文件
4. 更新EasyAdmin配置

### 扩展验证方式

1. 在`CertificateVerification`实体中添加新的验证方法
2. 在验证服务中实现对应的验证逻辑
3. 更新API接口和管理界面

### 自定义通知模板

1. 继承`CertificateNotificationService`
2. 重写对应的通知方法
3. 在服务配置中替换默认服务

## 版本历史

### v1.0.0 (2024-12-27)

- ✅ 完整的证书管理功能
- ✅ 证书模板系统
- ✅ 申请审核流程
- ✅ 证书生成和验证
- ✅ 命令行工具
- ✅ EasyAdmin管理界面
- ✅ 完整的测试覆盖

## 许可证

MIT License

## 贡献

欢迎提交Issue和Pull Request来改进这个包。

## 支持

如有问题，请通过以下方式联系：

- 提交GitHub Issue
- 发送邮件至开发团队
- 查看在线文档
