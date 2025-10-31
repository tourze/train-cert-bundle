<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;

/**
 * 证书验证实体
 * 用于记录证书验证的历史记录，包括验证方式、验证结果等信息
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: CertificateVerificationRepository::class)]
#[ORM\Table(name: 'job_training_certificate_verification', options: ['comment' => '证书验证记录'])]
class CertificateVerification implements ApiArrayInterface, \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: Certificate::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Certificate $certificate = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '验证方式'])]
    private string $verificationMethod;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Length(max: 200)]
    #[ORM\Column(length: 200, nullable: true, options: ['comment' => '验证者信息'])]
    private ?string $verifierInfo = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '验证结果', 'default' => 0])]
    private bool $verificationResult = false;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证详情'])]
    private ?array $verificationDetails = null;

    #[IndexColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Ip]
    #[Assert\Length(max: 45)]
    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '验证IP地址'])]
    private ?string $ipAddress = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'string')]
    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户代理'])]
    private ?string $userAgent = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false, options: ['comment' => '验证时间'])]
    private \DateTimeInterface $verificationTime;

    public function __construct()
    {
        $this->verificationTime = new \DateTimeImmutable();
        $this->verificationMethod = 'API';
    }

    public function __toString(): string
    {
        return $this->verificationMethod;
    }

    public function getCertificate(): ?Certificate
    {
        return $this->certificate;
    }

    public function setCertificate(?Certificate $certificate): void
    {
        $this->certificate = $certificate;
    }

    public function getVerificationMethod(): string
    {
        return $this->verificationMethod;
    }

    public function setVerificationMethod(string $verificationMethod): void
    {
        $this->verificationMethod = $verificationMethod;
    }

    public function getVerifierInfo(): ?string
    {
        return $this->verifierInfo;
    }

    public function setVerifierInfo(?string $verifierInfo): void
    {
        $this->verifierInfo = $verifierInfo;
    }

    public function isVerificationResult(): bool
    {
        return $this->verificationResult;
    }

    public function getVerificationResult(): bool
    {
        return $this->isVerificationResult();
    }

    public function setVerificationResult(bool $verificationResult): void
    {
        $this->verificationResult = $verificationResult;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVerificationDetails(): ?array
    {
        return $this->verificationDetails;
    }

    /**
     * @param array<string, mixed>|null $verificationDetails
     */
    public function setVerificationDetails(?array $verificationDetails): void
    {
        $this->verificationDetails = $verificationDetails;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getVerificationTime(): \DateTimeInterface
    {
        return $this->verificationTime;
    }

    public function setVerificationTime(\DateTimeInterface $verificationTime): void
    {
        $this->verificationTime = $verificationTime;
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
            'certificate' => $this->getCertificate()?->retrieveApiArray(),
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
