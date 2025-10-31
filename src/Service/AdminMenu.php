<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateAudit;
use Tourze\TrainCertBundle\Entity\CertificateRecord;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Entity\CertificateVerification;

/**
 * 培训证书管理菜单提供者
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('证书管理')) {
            $item->addChild('证书管理');
        }

        $certMenu = $item->getChild('证书管理');

        // 证书管理
        $certMenu
            ?->addChild('证书列表')
            ?->setUri($this->linkGenerator->getCurdListPage(Certificate::class))
            ?->setAttribute('icon', 'fas fa-certificate')
        ;

        // 证书申请管理
        $certMenu
            ?->addChild('证书申请')
            ?->setUri($this->linkGenerator->getCurdListPage(CertificateApplication::class))
            ?->setAttribute('icon', 'fas fa-file-alt')
        ;

        // 证书审核管理
        $certMenu
            ?->addChild('审核记录')
            ?->setUri($this->linkGenerator->getCurdListPage(CertificateAudit::class))
            ?->setAttribute('icon', 'fas fa-clipboard-check')
        ;

        // 证书记录管理
        $certMenu
            ?->addChild('证书记录')
            ?->setUri($this->linkGenerator->getCurdListPage(CertificateRecord::class))
            ?->setAttribute('icon', 'fas fa-archive')
        ;

        // 证书模板管理
        $certMenu
            ?->addChild('证书模板')
            ?->setUri($this->linkGenerator->getCurdListPage(CertificateTemplate::class))
            ?->setAttribute('icon', 'fas fa-file-contract')
        ;

        // 证书验证管理
        $certMenu
            ?->addChild('验证记录')
            ?->setUri($this->linkGenerator->getCurdListPage(CertificateVerification::class))
            ?->setAttribute('icon', 'fas fa-search')
        ;
    }
}
