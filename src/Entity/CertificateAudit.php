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
use Tourze\TrainCertBundle\Repository\CertificateAuditRepository;

/**
 * 证书审核实体
 * 用于记录证书申请的审核过程，包括审核状态、审核意见等信息
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: CertificateAuditRepository::class)]
#[ORM\Table(name: 'job_training_certificate_audit', options: ['comment' => '证书审核记录'])]
class CertificateAudit implements ApiArrayInterface, \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: CertificateApplication::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private CertificateApplication $application;

    #[IndexColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '审核状态', 'default' => 'pending'])]
    private string $auditStatus = 'pending';

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '审核结果'])]
    private ?string $auditResult = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'string')]
    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核意见'])]
    private ?string $auditComment = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '审核详情'])]
    private ?array $auditDetails = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '审核人'])]
    private ?string $auditor = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false, options: ['comment' => '审核时间'])]
    private \DateTimeInterface $auditTime;

    public function __construct()
    {
        $this->auditTime = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->auditStatus ?? '';
    }

    public function getApplication(): CertificateApplication
    {
        return $this->application;
    }

    public function setApplication(CertificateApplication $application): void
    {
        $this->application = $application;
    }

    public function getAuditStatus(): string
    {
        return $this->auditStatus;
    }

    public function setAuditStatus(string $auditStatus): void
    {
        $this->auditStatus = $auditStatus;
    }

    public function getAuditResult(): ?string
    {
        return $this->auditResult;
    }

    public function setAuditResult(?string $auditResult): void
    {
        $this->auditResult = $auditResult;
    }

    public function getAuditComment(): ?string
    {
        return $this->auditComment;
    }

    public function setAuditComment(?string $auditComment): void
    {
        $this->auditComment = $auditComment;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAuditDetails(): ?array
    {
        return $this->auditDetails;
    }

    /**
     * @param array<string, mixed>|null $auditDetails
     */
    public function setAuditDetails(?array $auditDetails): void
    {
        $this->auditDetails = $auditDetails;
    }

    public function getAuditor(): ?string
    {
        return $this->auditor;
    }

    public function setAuditor(?string $auditor): void
    {
        $this->auditor = $auditor;
    }

    public function getAuditTime(): \DateTimeInterface
    {
        return $this->auditTime;
    }

    public function setAuditTime(\DateTimeInterface $auditTime): void
    {
        $this->auditTime = $auditTime;
    }

    /**
     * 检查审核是否已完成
     */
    public function isCompleted(): bool
    {
        return in_array($this->auditStatus, ['approved', 'rejected', 'completed'], true);
    }

    /**
     * 检查审核是否通过
     */
    public function isApproved(): bool
    {
        return 'approved' === $this->auditResult;
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
            'application' => $this->getApplication()->retrieveApiArray(),
            'auditStatus' => $this->getAuditStatus(),
            'auditResult' => $this->getAuditResult(),
            'auditComment' => $this->getAuditComment(),
            'auditDetails' => $this->getAuditDetails(),
            'auditor' => $this->getAuditor(),
            'auditTime' => $this->getAuditTime()->format('Y-m-d H:i:s'),
            'isCompleted' => $this->isCompleted(),
            'isApproved' => $this->isApproved(),
        ];
    }
}
