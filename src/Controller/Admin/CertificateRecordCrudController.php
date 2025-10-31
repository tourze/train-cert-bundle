<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TrainCertBundle\Entity\CertificateRecord;

/**
 * 证书记录管理控制器
 *
 * @extends AbstractCrudController<CertificateRecord>
 */
#[AdminCrud(routePath: '/train-cert/certificate-record', routeName: 'train_cert_certificate_record')]
final class CertificateRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CertificateRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('证书记录')
            ->setEntityLabelInPlural('证书记录')
            ->setPageTitle('index', '证书记录管理')
            ->setPageTitle('new', '创建证书记录')
            ->setPageTitle('edit', '编辑证书记录')
            ->setPageTitle('detail', '证书记录详情')
            ->setDefaultSort(['issueDate' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $verify = Action::new('verify', '验证证书', 'fa fa-search')
            ->linkToCrudAction('verifyCertificate')
            ->setCssClass('btn btn-info')
        ;

        $renew = Action::new('renew', '续期证书', 'fa fa-refresh')
            ->linkToCrudAction('renewCertificate')
            ->setCssClass('btn btn-warning')
            ->displayIf(static function (CertificateRecord $record) {
                return null !== $record->getExpiryDate() && !$record->isExpired();
            })
        ;

        $revoke = Action::new('revoke', '撤销证书', 'fa fa-ban')
            ->linkToCrudAction('revokeCertificate')
            ->setCssClass('btn btn-danger')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $verify)
            ->add(Crud::PAGE_INDEX, $renew)
            ->add(Crud::PAGE_INDEX, $revoke)
            ->add(Crud::PAGE_DETAIL, $verify)
            ->add(Crud::PAGE_DETAIL, $renew)
            ->add(Crud::PAGE_DETAIL, $revoke)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('certificateType', '证书类型')
                ->setChoices([
                    '培训证书' => 'training',
                    '资格证书' => 'qualification',
                    '技能证书' => 'skill',
                    '安全证书' => 'safety',
                ]))
            ->add(DateTimeFilter::new('issueDate', '发证日期'))
            ->add(DateTimeFilter::new('expiryDate', '到期日期'))
            ->add('issuingAuthority')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            AssociationField::new('certificate', '关联证书')
                ->setRequired(true)
                ->autocomplete()
                ->setHelp('关联的证书实体'),

            TextField::new('certificateNumber', '证书编号')
                ->setRequired(true)
                ->setMaxLength(100)
                ->setHelp('唯一的证书编号'),

            ChoiceField::new('certificateType', '证书类型')
                ->setChoices([
                    '培训证书' => 'training',
                    '资格证书' => 'qualification',
                    '技能证书' => 'skill',
                    '安全证书' => 'safety',
                ])
                ->setRequired(true)
                ->renderExpanded(false),

            DateField::new('issueDate', '发证日期')
                ->setRequired(true)
                ->setFormat('yyyy-MM-dd')
                ->setHelp('证书颁发日期'),

            DateField::new('expiryDate', '到期日期')
                ->setFormat('yyyy-MM-dd')
                ->setHelp('证书有效期截止日期'),

            TextField::new('issuingAuthority', '发证机构')
                ->setRequired(true)
                ->setMaxLength(200)
                ->setHelp('颁发证书的机构名称'),

            TextField::new('verificationCode', '验证码')
                ->setRequired(true)
                ->setMaxLength(100)
                ->setHelp('用于验证证书真伪的代码'),

            ArrayField::new('metadata', '元数据')
                ->setHelp('证书相关的额外信息')
                ->hideOnIndex(),

            DateTimeField::new('createTime', '创建时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),

            DateTimeField::new('updateTime', '更新时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),
        ];
    }

    /**
     * 验证证书操作
     */
    #[AdminAction(routeName: 'verify_certificate', routePath: 'verify')]
    public function verifyCertificate(): Response
    {
        // TODO: 实现证书验证逻辑
        $this->addFlash('success', '证书验证完成');

        return $this->redirectToRoute('admin');
    }

    /**
     * 续期证书操作
     */
    #[AdminAction(routeName: 'renew_certificate', routePath: 'renew')]
    public function renewCertificate(): Response
    {
        // TODO: 实现证书续期逻辑
        $this->addFlash('success', '证书续期成功');

        return $this->redirectToRoute('admin');
    }

    /**
     * 撤销证书操作
     */
    #[AdminAction(routeName: 'revoke_certificate', routePath: 'revoke')]
    public function revokeCertificate(): Response
    {
        // TODO: 实现证书撤销逻辑
        $this->addFlash('warning', '证书已撤销');

        return $this->redirectToRoute('admin');
    }
}
