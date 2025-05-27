<?php

namespace Tourze\TrainCertBundle\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Tourze\TrainCertBundle\Entity\CertificateVerification;

/**
 * 证书验证管理控制器
 */
class CertificateVerificationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CertificateVerification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('证书验证')
            ->setEntityLabelInPlural('证书验证')
            ->setPageTitle('index', '证书验证记录')
            ->setPageTitle('detail', '验证记录详情')
            ->setDefaultSort(['verificationTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('verificationResult', '验证结果'))
            ->add(ChoiceFilter::new('verificationMethod', '验证方式')
                ->setChoices([
                    '证书编号' => 'certificate_number',
                    '验证码' => 'verification_code',
                    '二维码' => 'qr_code',
                ]))
            ->add(DateTimeFilter::new('verificationTime', '验证时间'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            AssociationField::new('certificate', '证书')
                ->autocomplete()
                ->formatValue(function ($value, $entity) {
                    if ($entity->getCertificate()) {
                        return $entity->getCertificate()->getTitle();
                    }
                    return '未关联证书';
                }),

            ChoiceField::new('verificationMethod', '验证方式')
                ->setChoices([
                    '证书编号' => 'certificate_number',
                    '验证码' => 'verification_code',
                    '二维码' => 'qr_code',
                ])
                ->renderExpanded(false),

            BooleanField::new('verificationResult', '验证结果')
                ->renderAsSwitch(false),

            TextField::new('verifierInfo', '验证者信息')
                ->hideOnIndex(),

            TextField::new('ipAddress', 'IP地址'),

            TextField::new('userAgent', '用户代理')
                ->hideOnIndex(),

            ArrayField::new('verificationDetails', '验证详情')
                ->setHelp('验证过程的详细信息')
                ->hideOnIndex(),

            DateTimeField::new('verificationTime', '验证时间')
                ->setFormat('yyyy-MM-dd HH:mm:ss'),
        ];
    }
} 