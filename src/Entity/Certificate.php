<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TrainCertBundle\Repository\CertificateRepository;

/**
 * 有一些省份，证书需要推送给省的监管平台的
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: CertificateRepository::class)]
#[ORM\Table(name: 'job_training_certificate', options: ['comment' => '证书记录'])]
class Certificate implements ApiArrayInterface, \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, options: ['comment' => '证书名'])]
    private string $title = '';

    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_write'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?UserInterface $user = null;

    #[Assert\Url]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '证书文件'])]
    private ?string $imgUrl = null;

    #[TrackColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
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
            'title' => $this->getTitle(),
            'imgUrl' => $this->getImgUrl(),
            'valid' => $this->isValid(),
        ];
    }

    public function getImgUrl(): ?string
    {
        return $this->imgUrl;
    }

    public function setImgUrl(?string $imgUrl): void
    {
        $this->imgUrl = $imgUrl;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }

    public function setCertificateNumber(string $certificateNumber): void
    {
        // 证书编号功能待实现
    }

    public function setCertificateType(string $certificateType): void
    {
        // 证书类型功能待实现
    }

    public function setHolderName(string $holderName): void
    {
        // 持有人姓名功能待实现
    }

    public function setIssueDate(\DateTimeInterface $issueDate): void
    {
        // 发放日期功能待实现
    }

    public function setExpiryDate(?\DateTimeInterface $expiryDate): void
    {
        // 到期日期功能待实现
    }
}
