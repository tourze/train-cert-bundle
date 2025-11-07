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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TrainCertBundle\Entity\CertificateApplication;

/**
 * 证书申请管理控制器
 *
 * @extends AbstractCrudController<CertificateApplication>
 */
#[AdminCrud(routePath: '/train-cert/certificate-application', routeName: 'train_cert_certificate_application')]
final class CertificateApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CertificateApplication::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('证书申请')
            ->setEntityLabelInPlural('证书申请')
            ->setPageTitle('index', '证书申请管理')
            ->setPageTitle('new', '创建证书申请')
            ->setPageTitle('edit', '编辑证书申请')
            ->setPageTitle('detail', '证书申请详情')
            ->setDefaultSort(['applicationTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', '审核通过', 'fa fa-check')
            ->linkToCrudAction('approveApplication')
            ->setCssClass('btn btn-success')
            ->displayIf(static function (CertificateApplication $application) {
                return 'pending' === $application->getApplicationStatus();
            })
        ;

        $reject = Action::new('reject', '审核拒绝', 'fa fa-times')
            ->linkToCrudAction('rejectApplication')
            ->setCssClass('btn btn-danger')
            ->displayIf(static function (CertificateApplication $application) {
                return 'pending' === $application->getApplicationStatus();
            })
        ;

        $issue = Action::new('issue', '发放证书', 'fa fa-certificate')
            ->linkToCrudAction('issueCertificate')
            ->setCssClass('btn btn-primary')
            ->displayIf(static function (CertificateApplication $application) {
                return 'approved' === $application->getApplicationStatus();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_INDEX, $issue)
            ->add(Crud::PAGE_DETAIL, $approve)
            ->add(Crud::PAGE_DETAIL, $reject)
            ->add(Crud::PAGE_DETAIL, $issue)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('applicationStatus', '申请状态')
                ->setChoices([
                    '待审核' => 'pending',
                    '已通过' => 'approved',
                    '已拒绝' => 'rejected',
                    '已发放' => 'issued',
                ]))
            ->add(ChoiceFilter::new('applicationType', '申请类型')
                ->setChoices([
                    '标准申请' => 'standard',
                    '续期申请' => 'renewal',
                    '升级申请' => 'upgrade',
                ]))
            ->add(DateTimeFilter::new('applicationTime', '申请时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            TextField::new('user', '申请用户')
                ->setRequired(true)
                ->formatValue(static function ($value) {
                    if (null === $value) {
                        return '';
                    }
                    return method_exists($value, 'getUserIdentifier')
                        ? $value->getUserIdentifier()
                        : (string) $value;
                })
                ->setHelp('用户标识符'),

            AssociationField::new('template', '证书模板')
                ->setRequired(true)
                ->autocomplete(),

            ChoiceField::new('applicationType', '申请类型')
                ->setChoices([
                    '标准申请' => 'standard',
                    '续期申请' => 'renewal',
                    '升级申请' => 'upgrade',
                ])
                ->setRequired(true)
                ->renderExpanded(false),

            ChoiceField::new('applicationStatus', '申请状态')
                ->setChoices([
                    '待审核' => 'pending',
                    '已通过' => 'approved',
                    '已拒绝' => 'rejected',
                    '已发放' => 'issued',
                ])
                ->hideOnForm(),

            ArrayField::new('applicationData', '申请数据')
                ->setHelp('申请相关的数据信息')
                ->hideOnIndex(),

            ArrayField::new('requiredDocuments', '必需文档')
                ->setHelp('申请所需的文档列表')
                ->hideOnIndex(),

            TextareaField::new('reviewComment', '审核意见')
                ->setNumOfRows(3)
                ->hideOnIndex(),

            TextField::new('reviewer', '审核人')
                ->hideOnForm(),

            DateTimeField::new('applicationTime', '申请时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),

            DateTimeField::new('reviewTime', '审核时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),

            DateTimeField::new('createTime', '创建时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),
        ];
    }

    /**
     * 审核通过操作
     */
    #[AdminAction(routePath: '/approve/{entityId}', routeName: 'train_cert_certificate_application_approve')]
    public function approveApplication(): Response
    {
        // TODO: 实现审核通过逻辑
        $this->addFlash('success', '申请审核通过');

        return $this->redirectToRoute('admin');
    }

    /**
     * 审核拒绝操作
     */
    #[AdminAction(routePath: '/reject/{entityId}', routeName: 'train_cert_certificate_application_reject')]
    public function rejectApplication(): Response
    {
        // TODO: 实现审核拒绝逻辑
        $this->addFlash('warning', '申请已拒绝');

        return $this->redirectToRoute('admin');
    }

    /**
     * 发放证书操作
     */
    #[AdminAction(routePath: '/issue/{entityId}', routeName: 'train_cert_certificate_application_issue')]
    public function issueCertificate(): Response
    {
        // TODO: 实现证书发放逻辑
        $this->addFlash('success', '证书发放成功');

        return $this->redirectToRoute('admin');
    }
}
