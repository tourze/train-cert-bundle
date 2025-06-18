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
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;

/**
 * 证书验证实体
 * 用于记录证书验证的历史记录，包括验证方式、验证结果等信息
 */
#[AsPermission(title: '证书验证')]
#[Deletable]
#[ORM\Entity(repositoryClass: CertificateVerificationRepository::class)]
#[ORM\Table(name: 'job_training_certificate_verification', options: ['comment' => '证书验证记录'])]
class CertificateVerification implements ApiArrayInterface, \Stringable
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

    #[ListColumn(order: 1)]
    #[FormField(order: 1)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: Certificate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Certificate $certificate;

    #[Keyword(inputWidth: 60, label: '验证方式')]
    #[ListColumn(order: 2)]
    #[FormField(order: 2)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '验证方式'])]
    private string $verificationMethod;

    #[ListColumn(order: 3)]
    #[FormField(order: 3)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 200, nullable: true, options: ['comment' => '验证者信息'])]
    private ?string $verifierInfo = null;

    #[BoolColumn]
    #[IndexColumn]
    #[ListColumn(order: 4)]
    #[FormField(order: 4)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '验证结果', 'default' => 0])]
    private bool $verificationResult = false;

    #[FormField(order: 5)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证详情'])]
    private ?array $verificationDetails = null;

    #[IndexColumn]
    #[ListColumn(order: 6)]
    #[FormField(order: 6)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '验证IP地址'])]
    private ?string $ipAddress = null;

    #[ListColumn(order: 7)]
    #[FormField(order: 7)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户代理'])]
    private ?string $userAgent = null;

    #[Filterable]
    #[ListColumn(order: 8, sorter: true)]
    #[FormField(order: 8)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, options: ['comment' => '验证时间'])]
    private \DateTimeInterface $verificationTime;

    public function __construct()
    {
        $this->verificationTime = new \DateTime();
    }

    public function __toString(): string
    {
        return sprintf(
            '证书验证 #%s - %s',
            $this->id ?? 'NEW',
            $this->verificationMethod ?? '未知方式'
        );
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

    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    public function setCertificate(Certificate $certificate): self
    {
        $this->certificate = $certificate;
        return $this;
    }

    public function getVerificationMethod(): string
    {
        return $this->verificationMethod;
    }

    public function setVerificationMethod(string $verificationMethod): self
    {
        $this->verificationMethod = $verificationMethod;
        return $this;
    }

    public function getVerifierInfo(): ?string
    {
        return $this->verifierInfo;
    }

    public function setVerifierInfo(?string $verifierInfo): self
    {
        $this->verifierInfo = $verifierInfo;
        return $this;
    }

    public function isVerificationResult(): bool
    {
        return $this->verificationResult;
    }

    public function setVerificationResult(bool $verificationResult): self
    {
        $this->verificationResult = $verificationResult;
        return $this;
    }

    public function getVerificationDetails(): ?array
    {
        return $this->verificationDetails;
    }

    public function setVerificationDetails(?array $verificationDetails): self
    {
        $this->verificationDetails = $verificationDetails;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getVerificationTime(): \DateTimeInterface
    {
        return $this->verificationTime;
    }

    public function setVerificationTime(\DateTimeInterface $verificationTime): self
    {
        $this->verificationTime = $verificationTime;
        return $this;
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'certificate' => $this->getCertificate()->retrieveApiArray(),
            'verificationMethod' => $this->getVerificationMethod(),
            'verifierInfo' => $this->getVerifierInfo(),
            'verificationResult' => $this->isVerificationResult(),
            'verificationDetails' => $this->getVerificationDetails(),
            'ipAddress' => $this->getIpAddress(),
            'userAgent' => $this->getUserAgent(),
            'verificationTime' => $this->getVerificationTime()->format('Y-m-d H:i:s'),
        ];
    }
} 