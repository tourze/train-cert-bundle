# Train Certificate Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/train-cert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/train-cert-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/train-cert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/train-cert-bundle)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](#)
[![License](https://img.shields.io/packagist/l/tourze/train-cert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/train-cert-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/train-cert-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/train-cert-bundle)

A comprehensive Symfony bundle for managing training certificates with full 
lifecycle support including application, approval, generation, and verification.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
  - [Generate a Certificate](#generate-a-certificate)
  - [Verify a Certificate](#verify-a-certificate)
  - [Using Console Commands](#using-console-commands)
- [Advanced Usage](#advanced-usage)
  - [Custom Certificate Templates](#custom-certificate-templates)
  - [Batch Certificate Generation](#batch-certificate-generation)
  - [Certificate Application Workflow](#certificate-application-workflow)
- [Core Components](#core-components)
  - [Entities](#entities)
  - [Services](#services)
  - [Console Commands](#console-commands)
  - [Admin Controllers](#admin-controllers)
- [Configuration](#configuration)
  - [Environment Variables](#environment-variables)
  - [Service Configuration](#service-configuration)
- [Security](#security)
  - [Anti-counterfeiting Features](#anti-counterfeiting-features)
  - [Access Control](#access-control)
  - [Best Practices](#best-practices)
  - [Security Configuration](#security-configuration)
- [Testing](#testing)
- [Dependencies](#dependencies)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Certificate Template Management** - Support for multiple certificate types 
  and custom template configurations
- **Application Workflow** - Complete application → approval → issuance 
  workflow
- **Certificate Generation** - Single and batch certificate generation 
  capabilities
- **Verification System** - Multiple verification methods (certificate number, 
  verification code)
- **Anti-counterfeiting** - Unique identifiers and verification code generation
- **Lifecycle Management** - Certificate validity management and expiration 
  reminders
- **Statistics & Reports** - Certificate issuance, verification, and expiration 
  statistics
- **Admin Interface** - EasyAdmin-based management backend

## Installation

```bash
composer require tourze/train-cert-bundle
```

## Requirements

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- EasyAdmin Bundle 4+

## Quick Start

### Generate a Certificate

```php
<?php

use Tourze\TrainCertBundle\Service\CertificateService;

// Generate certificate for a user
$certificateService = $container->get(CertificateService::class);
$certificate = $certificateService->generateCertificate(
    'user123',
    'course456',
    'template789'
);
```

### Verify a Certificate

```php
<?php

use Tourze\TrainCertBundle\Service\CertificateVerificationService;

// Verify certificate by certificate number
$verificationService = $container->get(CertificateVerificationService::class);
$result = $verificationService->verifyCertificate('CERT-20241201-123456');

if ($result->isValid()) {
    echo "Certificate is valid";
}
```

### Using Console Commands

```bash
# Generate certificates for multiple users
php bin/console certificate:generate template123 --user-ids=user1,user2,user3

# Generate statistics report
php bin/console certificate:statistics --format=json

# Clean up expired certificates
php bin/console certificate:cleanup --expired-days=365
```

## Advanced Usage

### Custom Certificate Templates

Create custom certificate templates with specific configurations:

```php
<?php

use Tourze\TrainCertBundle\Service\CertificateTemplateService;

$templateService = $container->get(CertificateTemplateService::class);
$template = $templateService->createTemplate([
    'templateName' => 'Safety Training Certificate',
    'templateType' => 'safety',
    'templatePath' => '/templates/safety.pdf',
    'templateConfig' => ['validityPeriod' => 365],
    'fieldMapping' => ['userName' => 'holder_name'],
    'isDefault' => true,
    'isActive' => true,
]);
```

### Batch Certificate Generation

Generate certificates for multiple users at once:

```php
<?php

$userIds = ['user1', 'user2', 'user3'];
$templateId = 'template789';
$config = ['issuingAuthority' => 'Training Institute'];

$certificates = $certificateService->generateBatchCertificates(
    $userIds, 
    $templateId, 
    $config
);
```

### Certificate Application Workflow

Implement the complete application workflow:

```php
<?php

// 1. Apply for certificate
$application = $certificateService->applyCertificate(
    'user123',
    'course456',
    ['templateId' => 'template789', 'type' => 'standard']
);

// 2. Audit the application
$audit = $certificateService->auditCertificate(
    $application->getId(),
    'approved',
    'Application meets all requirements'
);

// 3. Issue the certificate
$certificate = $certificateService->issueCertificate($application->getId());
```

## Core Components

### Entities

1. **Certificate** - Basic certificate information
2. **CertificateTemplate** - Template management
3. **CertificateApplication** - Application records
4. **CertificateRecord** - Detailed certificate records
5. **CertificateVerification** - Verification history
6. **CertificateAudit** - Audit records

### Services

1. **CertificateService** - Core business logic
2. **CertificateTemplateService** - Template management
3. **CertificateGeneratorService** - Certificate generation
4. **CertificateVerificationService** - Certificate verification
5. **CertificateNotificationService** - Notification service

### Console Commands

- `certificate:generate` - Generate certificates
- `certificate:statistics` - Generate statistics reports
- `certificate:cleanup` - Clean up expired data
- `job-training:generate-certificate-image` - Generate certificate images

### Admin Controllers

- `CertificateTemplateCrudController` - Template management
- `CertificateApplicationCrudController` - Application management
- `CertificateVerificationCrudController` - Verification management

## Configuration

### Environment Variables

```env
# Certificate file storage path
CERTIFICATE_STORAGE_PATH=/var/certificates

# Email notification configuration
MAILER_DSN=smtp://localhost:587

# Certificate verification salt
CERTIFICATE_VERIFY_SALT=your_secret_salt
```

### Service Configuration

```yaml
# config/services.yaml
services:
    Tourze\TrainCertBundle\Service\:
        resource: '../packages/train-cert-bundle/src/Service/'
        autowire: true
        autoconfigure: true
```

## Security

This bundle implements several security measures to ensure certificate integrity:

### Anti-counterfeiting Features

- **Unique Certificate Numbers** - Each certificate gets a unique identifier 
  (format: CERT-YYYYMMDD-XXXXXX)
- **Verification Codes** - SHA-256 based verification codes for additional 
  security
- **Template Validation** - Only active and validated templates can be used
- **Digital Signatures** - Support for digital signature integration

### Access Control

- **Role-based Permissions** - Certificate management requires appropriate roles
- **Audit Trail** - Complete audit log of all certificate operations
- **Secure Storage** - Certificate files stored with proper access controls

### Best Practices

- Always validate user permissions before certificate operations
- Implement rate limiting for certificate generation endpoints  
- Use HTTPS for all certificate-related API communications
- Regularly audit certificate issuance and verification logs

### Security Configuration

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/admin/certificate, roles: ROLE_CERTIFICATE_ADMIN }
        - { path: ^/api/certificate, roles: ROLE_API_USER }
```

## Testing

```bash
# Run all tests
./vendor/bin/phpunit packages/train-cert-bundle/tests/
```

## Dependencies

- `tourze/doctrine-timestamp-bundle` - Timestamp management
- `tourze/doctrine-snowflake-bundle` - Snowflake ID generation
- `tourze/doctrine-user-bundle` - User management
- `tourze/doctrine-indexed-bundle` - Database indexing

## Contributing

Contributions are welcome! Please submit issues and pull requests to improve this package.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.