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
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Filter\Keyword;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;
use Tourze\TrainCertBundle\Repository\CertificateAuditRepository;

/**
 * 证书审核实体
 * 用于记录证书申请的审核过程，包括审核状态、审核意见等信息
 */
#[AsPermission(title: '证书审核')]
#[Deletable]
#[ORM\Entity(repositoryClass: CertificateAuditRepository::class)]
#[ORM\Table(name: 'job_training_certificate_audit', options: ['comment' => '证书审核记录'])]
class CertificateAudit implements ApiArrayInterface, \Stringable
{
    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[Groups(['restful_read', 'admin_curd'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Groups(['restful_read', 'admin_curd'])]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    #[ExportColumn]
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
    #[ORM\ManyToOne(targetEntity: CertificateApplication::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CertificateApplication $application;

    #[IndexColumn]
    #[TrackColumn]
    #[Keyword(inputWidth: 60, label: '审核状态')]
    #[ListColumn(order: 2)]
    #[FormField(order: 2)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '审核状态', 'default' => 'pending'])]
    private string $auditStatus = 'pending';

    #[Keyword(inputWidth: 60, label: '审核结果')]
    #[ListColumn(order: 3)]
    #[FormField(order: 3)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '审核结果'])]
    private ?string $auditResult = null;

    #[ListColumn(order: 4)]
    #[FormField(order: 4)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核意见'])]
    private ?string $auditComment = null;

    #[FormField(order: 5)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '审核详情'])]
    private ?array $auditDetails = null;

    #[ListColumn(order: 6)]
    #[FormField(order: 6)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '审核人'])]
    private ?string $auditor = null;

    #[Filterable]
    #[ListColumn(order: 7, sorter: true)]
    #[FormField(order: 7)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, options: ['comment' => '审核时间'])]
    private \DateTimeInterface $auditTime;

    public function __construct()
    {
        $this->auditTime = new \DateTime();
    }

    public function __toString(): string
    {
        return sprintf(
            '证书审核 #%s - %s',
            $this->id ?? 'NEW',
            $this->auditStatus
        );
    }

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public function getId(): ?string
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

    public function getApplication(): CertificateApplication
    {
        return $this->application;
    }

    public function setApplication(CertificateApplication $application): self
    {
        $this->application = $application;
        return $this;
    }

    public function getAuditStatus(): string
    {
        return $this->auditStatus;
    }

    public function setAuditStatus(string $auditStatus): self
    {
        $this->auditStatus = $auditStatus;
        return $this;
    }

    public function getAuditResult(): ?string
    {
        return $this->auditResult;
    }

    public function setAuditResult(?string $auditResult): self
    {
        $this->auditResult = $auditResult;
        return $this;
    }

    public function getAuditComment(): ?string
    {
        return $this->auditComment;
    }

    public function setAuditComment(?string $auditComment): self
    {
        $this->auditComment = $auditComment;
        return $this;
    }

    public function getAuditDetails(): ?array
    {
        return $this->auditDetails;
    }

    public function setAuditDetails(?array $auditDetails): self
    {
        $this->auditDetails = $auditDetails;
        return $this;
    }

    public function getAuditor(): ?string
    {
        return $this->auditor;
    }

    public function setAuditor(?string $auditor): self
    {
        $this->auditor = $auditor;
        return $this;
    }

    public function getAuditTime(): \DateTimeInterface
    {
        return $this->auditTime;
    }

    public function setAuditTime(\DateTimeInterface $auditTime): self
    {
        $this->auditTime = $auditTime;
        return $this;
    }

    /**
     * 检查审核是否已完成
     */
    public function isCompleted(): bool
    {
        return in_array($this->auditStatus, ['approved', 'rejected', 'completed']);
    }

    /**
     * 检查审核是否通过
     */
    public function isApproved(): bool
    {
        return $this->auditResult === 'approved';
    }

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