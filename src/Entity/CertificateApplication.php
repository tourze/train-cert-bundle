<?php

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
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

/**
 * 证书申请实体
 * 用于管理证书申请流程，包括申请状态、审核流程等
 */
#[AsPermission(title: '证书申请')]
#[Deletable]
#[ORM\Entity]
#[ORM\Table(name: 'job_training_certificate_application', options: ['comment' => '证书申请'])]
class CertificateApplication implements ApiArrayInterface
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

    #[Keyword(inputWidth: 60, name: 'user.realName', label: '申请人姓名')]
    #[ListColumn(order: 1, title: '申请人')]
    #[FormField(order: 1)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $user;

    #[ListColumn(order: 2)]
    #[FormField(order: 2)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: CertificateTemplate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CertificateTemplate $template;

    #[Keyword(inputWidth: 60, label: '申请类型')]
    #[ListColumn(order: 3)]
    #[FormField(order: 3)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '申请类型'])]
    private string $applicationType;

    #[IndexColumn]
    #[TrackColumn]
    #[Keyword(inputWidth: 60, label: '申请状态')]
    #[ListColumn(order: 4)]
    #[FormField(order: 4)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '申请状态', 'default' => 'pending'])]
    private string $applicationStatus = 'pending';

    #[FormField(order: 5)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '申请数据'])]
    private ?array $applicationData = null;

    #[FormField(order: 6)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '必需文档'])]
    private ?array $requiredDocuments = null;

    #[ListColumn(order: 7)]
    #[FormField(order: 7)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核意见'])]
    private ?string $reviewComment = null;

    #[ListColumn(order: 8)]
    #[FormField(order: 8)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '审核人'])]
    private ?string $reviewer = null;

    #[Filterable]
    #[ListColumn(order: 9, sorter: true)]
    #[FormField(order: 9)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '申请时间'])]
    private ?\DateTimeInterface $applicationTime = null;

    #[Filterable]
    #[ListColumn(order: 10, sorter: true)]
    #[FormField(order: 10)]
    #[Groups(['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '审核时间'])]
    private ?\DateTimeInterface $reviewTime = null;public function getId(): ?string
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

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTemplate(): CertificateTemplate
    {
        return $this->template;
    }

    public function setTemplate(CertificateTemplate $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function getApplicationType(): string
    {
        return $this->applicationType;
    }

    public function setApplicationType(string $applicationType): self
    {
        $this->applicationType = $applicationType;
        return $this;
    }

    public function getApplicationStatus(): string
    {
        return $this->applicationStatus;
    }

    public function setApplicationStatus(string $applicationStatus): self
    {
        $this->applicationStatus = $applicationStatus;
        return $this;
    }

    public function getApplicationData(): ?array
    {
        return $this->applicationData;
    }

    public function setApplicationData(?array $applicationData): self
    {
        $this->applicationData = $applicationData;
        return $this;
    }

    public function getRequiredDocuments(): ?array
    {
        return $this->requiredDocuments;
    }

    public function setRequiredDocuments(?array $requiredDocuments): self
    {
        $this->requiredDocuments = $requiredDocuments;
        return $this;
    }

    public function getReviewComment(): ?string
    {
        return $this->reviewComment;
    }

    public function setReviewComment(?string $reviewComment): self
    {
        $this->reviewComment = $reviewComment;
        return $this;
    }

    public function getReviewer(): ?string
    {
        return $this->reviewer;
    }

    public function setReviewer(?string $reviewer): self
    {
        $this->reviewer = $reviewer;
        return $this;
    }

    public function getApplicationTime(): ?\DateTimeInterface
    {
        return $this->applicationTime;
    }

    public function setApplicationTime(?\DateTimeInterface $applicationTime): self
    {
        $this->applicationTime = $applicationTime;
        return $this;
    }

    public function getReviewTime(): ?\DateTimeInterface
    {
        return $this->reviewTime;
    }

    public function setReviewTime(?\DateTimeInterface $reviewTime): self
    {
        $this->reviewTime = $reviewTime;
        return $this;
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $this->getUser()->getUserIdentifier(),
                'username' => $this->getUser()->getUserIdentifier(),
            ],
            'template' => $this->getTemplate()->retrieveApiArray(),
            'applicationType' => $this->getApplicationType(),
            'applicationStatus' => $this->getApplicationStatus(),
            'applicationData' => $this->getApplicationData(),
            'requiredDocuments' => $this->getRequiredDocuments(),
            'reviewComment' => $this->getReviewComment(),
            'reviewer' => $this->getReviewer(),
            'applicationTime' => $this->getApplicationTime()?->format('Y-m-d H:i:s'),
            'reviewTime' => $this->getReviewTime()?->format('Y-m-d H:i:s'),
        ];
    }
} 