<?php

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;

/**
 * 证书验证实体
 * 用于记录证书验证的历史记录，包括验证方式、验证结果等信息
 */
#[ORM\Entity(repositoryClass: CertificateVerificationRepository::class)]
#[ORM\Table(name: 'job_training_certificate_verification', options: ['comment' => '证书验证记录'])]
class CertificateVerification implements ApiArrayInterface, \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: Certificate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Certificate $certificate;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '验证方式'])]
    private string $verificationMethod;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 200, nullable: true, options: ['comment' => '验证者信息'])]
    private ?string $verifierInfo = null;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '验证结果', 'default' => 0])]
    private bool $verificationResult = false;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证详情'])]
    private ?array $verificationDetails = null;

    #[IndexColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '验证IP地址'])]
    private ?string $ipAddress = null;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户代理'])]
    private ?string $userAgent = null;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false, options: ['comment' => '验证时间'])]
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
    }

    public function getId(): ?string
    {
        return $this->id;
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
