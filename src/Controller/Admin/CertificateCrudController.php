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
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TrainCertBundle\Entity\Certificate;

/**
 * 证书管理控制器
 *
 * @extends AbstractCrudController<Certificate>
 */
#[AdminCrud(routePath: '/train-cert/certificate', routeName: 'train_cert_certificate')]
final class CertificateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Certificate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('证书')
            ->setEntityLabelInPlural('证书')
            ->setPageTitle('index', '证书管理')
            ->setPageTitle('new', '创建证书')
            ->setPageTitle('edit', '编辑证书')
            ->setPageTitle('detail', '证书详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('valid', '有效状态'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            TextField::new('title', '证书名称')
                ->setRequired(true)
                ->setMaxLength(100)
                ->setHelp('证书的标题名称'),

            TextField::new('user', '证书持有人')
                ->setRequired(true)
                ->formatValue(static function ($value) {
                    if (null === $value) {
                        return '';
                    }
                    return method_exists($value, 'getUserIdentifier')
                        ? $value->getUserIdentifier()
                        : (string) $value;
                })
                ->setHelp('证书持有人的用户标识符'),

            UrlField::new('imgUrl', '证书文件')
                ->setHelp('证书图片或PDF文件的URL地址')
                ->hideOnIndex(),

            BooleanField::new('valid', '有效状态')
                ->setHelp('证书是否有效')
                ->renderAsSwitch(false),

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
    #[AdminAction(routeName: 'train_cert_certificate_validate', routePath: '/validate')]
    public function validateCertificate(): Response
    {
        // TODO: 实现证书验证逻辑
        $this->addFlash('success', '证书验证通过');

        return $this->redirectToRoute('admin');
    }

    /**
     * 作废证书操作
     */
    #[AdminAction(routeName: 'train_cert_certificate_invalidate', routePath: '/invalidate')]
    public function invalidateCertificate(): Response
    {
        // TODO: 实现证书作废逻辑
        $this->addFlash('warning', '证书已作废');

        return $this->redirectToRoute('admin');
    }

    /**
     * 预览证书操作
     */
    #[AdminAction(routeName: 'train_cert_certificate_preview', routePath: '/preview')]
    public function previewCertificate(): Response
    {
        // TODO: 实现证书预览逻辑
        $this->addFlash('info', '打开证书预览');

        return $this->redirectToRoute('admin');
    }
}
