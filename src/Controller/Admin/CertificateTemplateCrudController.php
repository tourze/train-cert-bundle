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
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;

/**
 * 证书模板管理控制器
 *
 * @extends AbstractCrudController<CertificateTemplate>
 */
#[AdminCrud(routePath: '/train-cert/certificate-template', routeName: 'train_cert_certificate_template')]
final class CertificateTemplateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CertificateTemplate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('证书模板')
            ->setEntityLabelInPlural('证书模板')
            ->setPageTitle('index', '证书模板管理')
            ->setPageTitle('new', '创建证书模板')
            ->setPageTitle('edit', '编辑证书模板')
            ->setPageTitle('detail', '证书模板详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $duplicate = Action::new('duplicate', '复制模板', 'fa fa-copy')
            ->linkToCrudAction('duplicateTemplate')
            ->setCssClass('btn btn-info')
        ;

        $preview = Action::new('preview', '预览模板', 'fa fa-eye')
            ->linkToCrudAction('previewTemplate')
            ->setCssClass('btn btn-success')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_INDEX, $preview)
            ->add(Crud::PAGE_DETAIL, $duplicate)
            ->add(Crud::PAGE_DETAIL, $preview)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('templateType', '模板类型')
                ->setChoices([
                    '安全生产证书' => 'safety',
                    '技能培训证书' => 'skill',
                    '管理培训证书' => 'management',
                    '特种作业证书' => 'special',
                ]))
            ->add(BooleanFilter::new('isDefault', '默认模板'))
            ->add(BooleanFilter::new('isActive', '启用状态'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            TextField::new('templateName', '模板名称')
                ->setRequired(true)
                ->setHelp('证书模板的显示名称'),

            ChoiceField::new('templateType', '模板类型')
                ->setChoices([
                    '安全生产证书' => 'safety',
                    '技能培训证书' => 'skill',
                    '管理培训证书' => 'management',
                    '特种作业证书' => 'special',
                ])
                ->setRequired(true)
                ->renderExpanded(false),

            TextField::new('templatePath', '模板路径')
                ->setHelp('模板文件的存储路径')
                ->hideOnIndex(),

            ArrayField::new('templateConfig', '模板配置')
                ->setHelp('模板的配置参数，JSON格式')
                ->hideOnIndex(),

            ArrayField::new('fieldMapping', '字段映射')
                ->setHelp('模板字段与数据字段的映射关系')
                ->hideOnIndex(),

            BooleanField::new('isDefault', '默认模板')
                ->setHelp('是否为该类型的默认模板'),

            BooleanField::new('isActive', '启用状态')
                ->setHelp('是否启用此模板'),

            TextareaField::new('templateContent', '模板内容')
                ->setHelp('证书模板的实际内容')
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
     * 复制模板操作
     */
    #[AdminAction(routePath: '{entityId}/duplicate', routeName: 'duplicateTemplate')]
    public function duplicateTemplate(): Response
    {
        // TODO: 实现模板复制逻辑
        $this->addFlash('success', '模板复制功能待实现');

        return $this->redirectToRoute('admin');
    }

    /**
     * 预览模板操作
     */
    #[AdminAction(routePath: '{entityId}/preview', routeName: 'previewTemplate')]
    public function previewTemplate(): Response
    {
        // TODO: 实现模板预览逻辑
        $this->addFlash('info', '模板预览功能待实现');

        return $this->redirectToRoute('admin');
    }
}
