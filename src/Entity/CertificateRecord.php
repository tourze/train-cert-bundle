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

/**
 * 证书记录实体
 * 用于记录证书的详细信息，包括证书编号、有效期、验证码等核心数据
 */
#[ORM\Entity]
#[ORM\Table(name: 'job_training_certificate_record', options: ['comment' => '证书记录'])]
class CertificateRecord implements ApiArrayInterface
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
    #[ORM\OneToOne(targetEntity: Certificate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Certificate $certificate;

    #[IndexColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 100, nullable: false, unique: true, options: ['comment' => '证书编号'])]
    private string $certificateNumber;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '证书类型'])]
    private string $certificateType;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: false, options: ['comment' => '发证日期'])]
    private \DateTimeInterface $issueDate;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => '到期日期'])]
    private ?\DateTimeInterface $expiryDate = null;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 200, nullable: false, options: ['comment' => '发证机构'])]
    private string $issuingAuthority;

    #[IndexColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 100, nullable: false, unique: true, options: ['comment' => '验证码'])]
    private string $verificationCode;

    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    private ?array $metadata = null;

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

    public function getCertificateNumber(): string
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(string $certificateNumber): self
    {
        $this->certificateNumber = $certificateNumber;
        return $this;
    }

    public function getCertificateType(): string
    {
        return $this->certificateType;
    }

    public function setCertificateType(string $certificateType): self
    {
        $this->certificateType = $certificateType;
        return $this;
    }

    public function getIssueDate(): \DateTimeInterface
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeInterface $issueDate): self
    {
        $this->issueDate = $issueDate;
        return $this;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    public function getIssuingAuthority(): string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(string $issuingAuthority): self
    {
        $this->issuingAuthority = $issuingAuthority;
        return $this;
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(string $verificationCode): self
    {
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * 检查证书是否已过期
     */
    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate < new \DateTime();
    }

    /**
     * 获取证书剩余有效天数
     */
    public function getRemainingDays(): ?int
    {
        if ($this->expiryDate === null) {
            return null;
        }

        $now = new \DateTime();
        $diff = $now->diff($this->expiryDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

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
        return (string)$this->id;
    }
}
