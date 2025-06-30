<?php

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 证书申请实体
 * 用于管理证书申请流程，包括申请状态、审核流程等
 */
#[ORM\Entity]
#[ORM\Table(name: 'job_training_certificate_application', options: ['comment' => '证书申请'])]
class CertificateApplication implements ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $user;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: CertificateTemplate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CertificateTemplate $template;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '申请类型'])]
    private string $applicationType;

    #[IndexColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '申请状态', 'default' => 'pending'])]
    private string $applicationStatus = 'pending';

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '申请数据'])]
    private ?array $applicationData = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '必需文档'])]
    private ?array $requiredDocuments = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核意见'])]
    private ?string $reviewComment = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '审核人'])]
    private ?string $reviewer = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '申请时间'])]
    private ?\DateTimeInterface $applicationTime = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '审核时间'])]
    private ?\DateTimeInterface $reviewTime = null;


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

    public function __toString(): string
    {
        return (string)$this->id;
    }
}
