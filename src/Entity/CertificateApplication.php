<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 证书申请实体
 * 用于管理证书申请流程，包括申请状态、审核流程等
 * @implements ApiArrayInterface<string, mixed>
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
    #[ORM\JoinColumn(nullable: true)]
    private ?UserInterface $user = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne(targetEntity: CertificateTemplate::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private CertificateTemplate $template;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '申请类型'])]
    private string $applicationType;

    #[IndexColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: false, options: ['comment' => '申请状态', 'default' => 'pending'])]
    private string $applicationStatus = 'pending';

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '申请数据'])]
    private ?array $applicationData = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '必需文档'])]
    private ?array $requiredDocuments = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'string')]
    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核意见'])]
    private ?string $reviewComment = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '审核人'])]
    private ?string $reviewer = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: '\DateTimeInterface')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '申请时间'])]
    private ?\DateTimeInterface $applicationTime = null;

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: '\DateTimeInterface')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '审核时间'])]
    private ?\DateTimeInterface $reviewTime = null;

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getTemplate(): CertificateTemplate
    {
        return $this->template;
    }

    public function setTemplate(CertificateTemplate $template): void
    {
        $this->template = $template;
    }

    public function getApplicationType(): string
    {
        return $this->applicationType;
    }

    public function setApplicationType(string $applicationType): void
    {
        $this->applicationType = $applicationType;
    }

    public function getApplicationStatus(): string
    {
        return $this->applicationStatus;
    }

    public function setApplicationStatus(string $applicationStatus): void
    {
        $this->applicationStatus = $applicationStatus;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getApplicationData(): ?array
    {
        return $this->applicationData;
    }

    /**
     * @param array<string, mixed>|null $applicationData
     */
    public function setApplicationData(?array $applicationData): void
    {
        $this->applicationData = $applicationData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequiredDocuments(): ?array
    {
        return $this->requiredDocuments;
    }

    /**
     * @param array<string, mixed>|null $requiredDocuments
     */
    public function setRequiredDocuments(?array $requiredDocuments): void
    {
        $this->requiredDocuments = $requiredDocuments;
    }

    public function getReviewComment(): ?string
    {
        return $this->reviewComment;
    }

    public function setReviewComment(?string $reviewComment): void
    {
        $this->reviewComment = $reviewComment;
    }

    public function getReviewer(): ?string
    {
        return $this->reviewer;
    }

    public function setReviewer(?string $reviewer): void
    {
        $this->reviewer = $reviewer;
    }

    public function getApplicationTime(): ?\DateTimeInterface
    {
        return $this->applicationTime;
    }

    public function setApplicationTime(?\DateTimeInterface $applicationTime): void
    {
        $this->applicationTime = $applicationTime;
    }

    public function getReviewTime(): ?\DateTimeInterface
    {
        return $this->reviewTime;
    }

    public function setReviewTime(?\DateTimeInterface $reviewTime): void
    {
        $this->reviewTime = $reviewTime;
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
            'user' => null !== $this->getUser() ? [
                'id' => $this->getUser()->getUserIdentifier(),
                'username' => $this->getUser()->getUserIdentifier(),
            ] : null,
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
        return $this->applicationType;
    }
}
