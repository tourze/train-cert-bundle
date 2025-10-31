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

/**
 * 证书记录实体
 * 用于记录证书的详细信息，包括证书编号、有效期、验证码等核心数据
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity]
#[ORM\Table(name: 'job_training_certificate_record', options: ['comment' => '证书记录'])]
class CertificateRecord implements ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\OneToOne(targetEntity: Certificate::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private Certificate $certificate;

    #[IndexColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: false, unique: true, options: ['comment' => '证书编号'])]
    private string $certificateNumber;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '证书类型'])]
    private string $certificateType;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: false, options: ['comment' => '发证日期'])]
    private \DateTimeInterface $issueDate;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: '\DateTimeInterface')]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '到期日期'])]
    private ?\DateTimeInterface $expiryDate = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[ORM\Column(length: 200, nullable: false, options: ['comment' => '发证机构'])]
    private string $issuingAuthority;

    #[IndexColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: false, unique: true, options: ['comment' => '验证码'])]
    private string $verificationCode;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    private ?array $metadata = null;

    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    public function setCertificate(Certificate $certificate): void
    {
        $this->certificate = $certificate;
    }

    public function getCertificateNumber(): string
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(string $certificateNumber): void
    {
        $this->certificateNumber = $certificateNumber;
    }

    public function getCertificateType(): string
    {
        return $this->certificateType;
    }

    public function setCertificateType(string $certificateType): void
    {
        $this->certificateType = $certificateType;
    }

    public function getIssueDate(): \DateTimeInterface
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeInterface $issueDate): void
    {
        $this->issueDate = $issueDate;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeInterface $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    public function getIssuingAuthority(): string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(string $issuingAuthority): void
    {
        $this->issuingAuthority = $issuingAuthority;
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(string $verificationCode): void
    {
        $this->verificationCode = $verificationCode;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * 检查证书是否已过期
     */
    public function isExpired(): bool
    {
        if (null === $this->expiryDate) {
            return false;
        }

        return $this->expiryDate < new \DateTimeImmutable();
    }

    /**
     * 获取证书剩余有效天数
     */
    public function getRemainingDays(): ?int
    {
        if (null === $this->expiryDate) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->expiryDate);

        return 1 === $diff->invert ? -(int) $diff->days : (int) $diff->days;
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
            'certificate' => $this->getCertificate()->retrieveApiArray(),
            'certificateNumber' => $this->getCertificateNumber(),
            'certificateType' => $this->getCertificateType(),
            'issueDate' => $this->getIssueDate()->format('Y-m-d'),
            'expiryDate' => $this->getExpiryDate()?->format('Y-m-d'),
            'issuingAuthority' => $this->getIssuingAuthority(),
            'verificationCode' => $this->getVerificationCode(),
            'metadata' => $this->getMetadata(),
            'isExpired' => $this->isExpired(),
            'remainingDays' => $this->getRemainingDays(),
        ];
    }

    public function __toString(): string
    {
        return $this->certificateNumber;
    }
}
